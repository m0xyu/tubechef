<?php

namespace App\Actions;

use App\Enums\Errors\RecipeError;
use App\Enums\RecipeGenerationStatus;
use App\Exceptions\RecipeException;
use App\Models\Dish;
use App\Models\Recipe;
use App\Models\Video;
use App\Services\LLM\LLMServiceFactory;
use App\Services\LLM\LLMServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GenerateRecipeAction
{
    protected LLMServiceInterface $llmService;

    public function __construct(
        LLMServiceFactory $factory
    ) {
        $this->llmService = $factory->make();
    }

    /**
     * 動画のタイトルと説明文からレシピを生成し、保存する
     * @param Video $video
     * @return Recipe
     * @throws \RuntimeException
     */
    public function execute(Video $video): Recipe
    {
        if ($video->recipe()->exists()) {
            return $video->recipe;
        }

        try {
            $result = $this->llmService->generateRecipe($video->title, $video->description ?? '', $video->url);
            Log::info("Gemini生成成功: VideoID {$video->id}", ['result' => $result]);
        } catch (Throwable $e) {
            Log::error("Gemini生成エラー: VideoID {$video->id}", ['error' => $e->getMessage()]);
            throw new RecipeException(RecipeError::GENERATION_FAILED, previous: $e);
        }

        if (empty($result['is_recipe'])) {
            throw new RecipeException(RecipeError::NOT_A_RECIPE);
        }

        try {
            return DB::transaction(function () use ($video, $result) {
                $recipe = $this->saveRecipe($video, $result);

                $video->update([
                    'recipe_generation_status' => RecipeGenerationStatus::COMPLETED,
                    'recipe_error_message' => null,
                ]);

                return $recipe;
            });
        } catch (Throwable $e) {
            throw new RecipeException(RecipeError::SAVE_FAILED, previous: $e);
        }
    }

    /**
     * 生成されたレシピデータをデータベースに保存する
     * 
     * @param Video $video
     * @param array<string, mixed> $result
     * @return Recipe
     */
    private function saveRecipe(Video $video, array $result): Recipe
    {
        $dish = Dish::firstOrCreate(
            ['slug' => $result['dish_slug']],
            ['name' => $result['dish_name']]
        );

        $recipe = Recipe::create([
            'video_id' => $video->id,
            'dish_id' => $dish->id,
            'slug' => $video->video_id,
            'title' => $result['title'],
            'summary' => $result['summary'] ?? null,
            'serving_size' => $result['serving_size'] ?? null,
            'cooking_time' => $result['cooking_time'] ?? null,
        ]);

        $now = now(); // 一括挿入時はタイムスタンプが自動付与されないため

        $ingredients = collect($result['ingredients'])->map(function ($item, $index) use ($recipe, $now) {
            return [
                'recipe_id' => $recipe->id,
                'name'      => $item['name'],
                'quantity'  => $item['quantity'] ?? null,
                'group'     => $item['group'] ?? null,
                'order'     => $index,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();
        $recipe->ingredients()->insert($ingredients);

        $stepNumberToIdMap = [];
        foreach ($result['steps'] as $stepData) {
            $step = $recipe->steps()->create([
                'step_number' => $stepData['step_number'],
                'description' => $stepData['description'],
                'start_time_in_seconds' => $stepData['start_time_in_seconds'] ?? null,
                'end_time_in_seconds' => $stepData['end_time_in_seconds'] ?? null,
            ]);
            $stepNumberToIdMap[$stepData['step_number']] = $step->id;
        }

        if (!empty($result['tips'])) {
            $tips = collect($result['tips'])->map(function ($item) use ($recipe, $stepNumberToIdMap, $now) {
                $relatedStepId = null;
                if (isset($item['related_step_number']) && isset($stepNumberToIdMap[$item['related_step_number']])) {
                    $relatedStepId = $stepNumberToIdMap[$item['related_step_number']];
                }

                return [
                    'recipe_id'      => $recipe->id,
                    'recipe_step_id' => $relatedStepId,
                    'description'    => $item['description'],
                    'start_time_in_seconds' => $item['start_time_in_seconds'] ?? null,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            })->toArray();
            $recipe->tips()->insert($tips);
        }

        return $recipe;
    }
}
