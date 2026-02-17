<?php

namespace App\Actions;

use App\Dtos\GeneratedRecipeData;
use App\Dtos\IngredientData;
use App\Dtos\RecipeTipData;
use App\Enums\Errors\RecipeError;
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

        if (!$result->isRecipe) {
            throw new RecipeException(RecipeError::NOT_A_RECIPE);
        }

        try {
            return DB::transaction(function () use ($video, $result) {
                $recipe = $this->saveRecipe($video, $result);

                $video->markAsCompleted();

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
     * @param GeneratedRecipeData $result
     * @return Recipe
     */
    private function saveRecipe(Video $video, GeneratedRecipeData $result): Recipe
    {
        $dish = Dish::firstOrCreate(
            ['slug' => $result->dishSlug],
            ['name' => $result->dishName]
        );

        $recipe = Recipe::create([
            'video_id' => $video->id,
            'dish_id' => $dish->id,
            'slug' => $video->video_id,
            'title' => $result->title,
            'summary' => $result->summary ?? null,
            'serving_size' => $result->servingSize ?? null,
            'cooking_time' => $result->cookingTime ?? null,
        ]);

        $now = now(); // 一括挿入時はタイムスタンプが自動付与されないため

        $ingredients = collect($result->ingredients)->map(function (IngredientData $item) use ($recipe, $now) {
            return [
                'recipe_id'  => $recipe->id,
                'name'       => $item->name,
                'quantity'   => $item->quantity,
                'group'      => $item->group,
                'order'      => $item->order,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();
        $recipe->ingredients()->insert($ingredients);

        $stepNumberToIdMap = [];
        foreach ($result->steps as $stepData) {
            $step = $recipe->steps()->create([
                'step_number'           => $stepData->stepNumber,
                'description'           => $stepData->description,
                'start_time_in_seconds' => $stepData->startTimeInSeconds,
                'end_time_in_seconds'   => $stepData->endTimeInSeconds,
            ]);
            $stepNumberToIdMap[$stepData->stepNumber] = $step->id;
        }

        if (!empty($result->tips)) {
            $tips = collect($result->tips)->map(function (RecipeTipData $item) use ($recipe, $stepNumberToIdMap, $now) {
                $relatedStepId = $stepNumberToIdMap[$item->relatedStepNumber] ?? null;

                return [
                    'recipe_id'             => $recipe->id,
                    'recipe_step_id'        => $relatedStepId,
                    'description'           => $item->description,
                    'start_time_in_seconds' => $item->startTimeInSeconds,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ];
            })->toArray();
            $recipe->tips()->insert($tips);
        }

        return $recipe;
    }
}
