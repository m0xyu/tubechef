<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\Video;
use App\Models\Dish;
use App\Dtos\GeneratedRecipeData;
use App\Dtos\IngredientData;
use App\Dtos\RecipeTipData;
use Illuminate\Support\Facades\DB;

class RecipeService
{
    /**
     * 生成されたレシピデータをDBに保存する
     */
    public function storeGeneratedRecipe(Video $video, GeneratedRecipeData $data): Recipe
    {
        return DB::transaction(function () use ($video, $data) {
            // 1. 料理（Dish）の取得または作成
            $dish = Dish::firstOrCreate(
                ['slug' => $data->dishSlug],
                ['name' => $data->dishName]
            );

            // 2. レシピ（Recipe）の基本情報の保存
            $recipe = Recipe::create([
                'video_id' => $video->id,
                'dish_id' => $dish->id,
                'slug' => $video->video_id,
                'title' => $data->title,
                'summary' => $data->summary ?? null,
                'serving_size' => $data->servingSize ?? null,
                'cooking_time' => $data->cookingTime ?? null,
            ]);

            $now = now();

            // 3. 材料（Ingredients）の一括挿入
            $ingredients = collect($data->ingredients)->map(function (IngredientData $item) use ($recipe, $now) {
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

            // 4. 手順（Steps）の保存とIDマッピング
            $stepNumberToIdMap = [];
            foreach ($data->steps as $stepData) {
                $step = $recipe->steps()->create([
                    'step_number'           => $stepData->stepNumber,
                    'description'           => $stepData->description,
                    'start_time_in_seconds' => $stepData->startTimeInSeconds,
                    'end_time_in_seconds'   => $stepData->endTimeInSeconds,
                ]);
                $stepNumberToIdMap[$stepData->stepNumber] = $step->id;
            }

            // 5. コツ（Tips）の一括挿入
            if (!empty($data->tips)) {
                $tips = collect($data->tips)->map(function (RecipeTipData $item) use ($recipe, $stepNumberToIdMap, $now) {
                    return [
                        'recipe_id'             => $recipe->id,
                        'recipe_step_id'        => $stepNumberToIdMap[$item->relatedStepNumber] ?? null,
                        'description'           => $item->description,
                        'start_time_in_seconds' => $item->startTimeInSeconds,
                        'created_at'            => $now,
                        'updated_at'            => $now,
                    ];
                })->toArray();
                $recipe->tips()->insert($tips);
            }

            return $recipe;
        });
    }
}
