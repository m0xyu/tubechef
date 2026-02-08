<?php

namespace App\Actions;

use App\Enums\Errors\RecipeError;
use App\Enums\RecipeGenerationStatus;
use App\Exceptions\RecipeException;
use App\Models\Dish;
use App\Models\Recipe;
use App\Models\Video;
use App\Services\GeminiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GenerateRecipeAction
{
    public function __construct(
        protected GeminiService $geminiService
    ) {}

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
            $result = $this->geminiService->generateRecipe($video->title, $video->description ?? '', $video->url);
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

        foreach ($result['ingredients'] as $index => $ingredientData) {
            $recipe->ingredients()->create([
                'name' => $ingredientData['name'],
                'quantity' => $ingredientData['quantity'],
                'group' => $ingredientData['group'] ?? null,
                'order' => $index,
            ]);
        }

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
            foreach ($result['tips'] as $tipData) {
                // 関連するステップ番号があれば、DBのIDに変換する
                $relatedStepId = null;
                if (isset($tipData['related_step_number']) && isset($stepNumberToIdMap[$tipData['related_step_number']])) {
                    $relatedStepId = $stepNumberToIdMap[$tipData['related_step_number']];
                }

                $recipe->tips()->create([
                    'description' => $tipData['description'],
                    'recipe_step_id' => $relatedStepId,
                    'start_time_in_seconds' => $tipData['start_time_in_seconds'] ?? null,
                ]);
            }
        }

        return $recipe;
    }
}
