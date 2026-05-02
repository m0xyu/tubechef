<?php

namespace App\Actions;

use App\Dtos\GeneratedRecipeData;
use App\Enums\Errors\RecipeError;
use App\Exceptions\RecipeException;
use App\Models\Recipe;
use App\Models\Video;
use App\Services\LLM\LLMServiceInterface;
use App\Services\LLM\Prompts\RecipePrompt;
use App\Services\RecipeService;
use App\Services\LLM\Schemas\RecipeSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateRecipeAction
{
    protected LLMServiceInterface $llmService;

    public function __construct(
        LLMServiceInterface $llmService,
        protected RecipeService $recipeService,
        protected VideoMetadataUpdateAction $videoMetadataUpdateAction

    ) {
        $this->llmService = $llmService;
    }

    /**
     * 動画のタイトルと説明文からレシピを生成し、保存する
     * @param Video $video
     * @return Recipe
     * @throws \RuntimeException
     */
    public function execute(Video $video): Recipe
    {
        $existing = $video->recipe;
        if ($existing instanceof Recipe) {
            return $existing;
        }

        try {
            $result = $this->llmService->generateStructured(
                RecipePrompt::build($video),
                RecipeSchema::get(),
                RecipePrompt::systemInstruction(),
                $video->url
            );
            Log::info("Gemini生成成功: VideoID {$video->id}");
        } catch (Throwable $e) {
            Log::error("Gemini生成エラー: VideoID {$video->id}", ['error' => $e->getMessage()]);
            throw new RecipeException(RecipeError::GENERATION_FAILED, previous: $e);
        }

        $recipeData = GeneratedRecipeData::fromArray($result->data);
        /** @var array<string, string> $metadataForUpdate */
        $metadataForUpdate = collect($result->usage)
            ->filter(fn($value) => is_scalar($value))
            ->map(fn($value) => (string) $value)
            ->toArray();

        if (!$recipeData->isRecipe) {
            $this->videoMetadataUpdateAction->execute($video, $metadataForUpdate);
            throw new RecipeException(RecipeError::NOT_A_RECIPE);
        }

        try {
            return DB::transaction(function () use ($video, $recipeData, $metadataForUpdate) {
                $recipe = $this->recipeService->storeGeneratedRecipe($video, $recipeData);

                $this->videoMetadataUpdateAction->execute($video, $metadataForUpdate);
                $video->markAsCompleted();

                return $recipe;
            });
        } catch (Throwable $e) {
            Log::error("レシピ保存エラー: VideoID {$video->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new RecipeException(RecipeError::SAVE_FAILED, previous: $e);
        }
    }
}
