<?php

declare(strict_types=1);

namespace App\Actions;

use App\Dtos\GeneratedRecipeData;
use App\Dtos\LLMRequestData;
use App\Enums\Errors\RecipeError;
use App\Exceptions\RecipeException;
use App\Models\Recipe;
use App\Models\Video;
use App\Services\LLM\LLMServiceInterface;
use App\Services\RecipeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Throwable;

class GenerateRecipeAction
{

    public function __construct(
        private readonly LLMServiceInterface $llmService,
        private readonly RecipeService $recipeService,
        private readonly VideoMetadataUpdateAction $videoMetadataUpdateAction,
        private readonly DenormalizerInterface $denormalizer,
    ) {}

    /**
     * 動画のタイトルと説明文からレシピを生成し、保存する
     * @param Video $video
     * @return Recipe
     * @throws RecipeException
     */
    public function execute(Video $video): Recipe
    {
        $existing = $video->recipe;
        if ($existing instanceof Recipe) {
            return $existing;
        }

        try {
            $result = $this->llmService->generate(LLMRequestData::fromVideo($video));
            Log::info("LLM生成成功: VideoID {$video->id}");
        } catch (Throwable $e) {
            Log::error("LLM生成エラー: VideoID {$video->id}", ['error' => $e->getMessage()]);
            throw new RecipeException(RecipeError::GENERATION_FAILED, previous: $e);
        }

        $recipeData = $this->denormalizer->denormalize($result->data, GeneratedRecipeData::class);

        $metadataForUpdate = array_merge($result->metadata, [
            'evaluated_at' => now()->toDateTimeString(),
        ]);

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
