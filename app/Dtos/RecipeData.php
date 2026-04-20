<?php

namespace App\Dtos;

use App\Dtos\DishData;
use App\Models\Recipe;

/**
 * @param int $id
 * @param string $title
 * @param string $slug
 * @param string|null $summary
 * @param string|null $servingSize
 * @param string|null $cookingTime
 * @param array<IngredientData> $ingredients
 * @param array<RecipeStepData> $steps
 * @param array<RecipeTipData> $tips
 * @param DishData|null $dish
 * @param VideoData|null $video
 */
final readonly class RecipeData
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public ?string $summary,
        public ?string $servingSize,
        public ?string $cookingTime,
        public array $ingredients,
        public array $steps,
        public array $tips,
        public ?DishData $dish = null,
        public ?VideoData $video = null,
    ) {}

    /**
     * Recipeモデル（とそのリレーション）からDTOを生成する
     */
    public static function fromModel(Recipe $recipe): self
    {
        $ingredients = $recipe->relationLoaded('ingredients')
            ? $recipe->ingredients->map(fn($i) => new IngredientData(
                name: $i->name,
                quantity: $i->quantity,
                group: $i->group,
                order: $i->order
            ))->toArray()
            : [];

        $steps = $recipe->relationLoaded('steps')
            ? $recipe->steps->map(fn($s) => new RecipeStepData(
                stepNumber: $s->step_number,
                description: $s->description,
                startTimeInSeconds: $s->start_time_in_seconds,
                endTimeInSeconds: $s->end_time_in_seconds,
                tips: $s->relationLoaded('tips') ? $s->tips->map(fn($t) => new RecipeTipData(
                    description: $t->description,
                    relatedStepNumber: $t->recipe_step_id,
                    startTimeInSeconds: $t->start_time_in_seconds
                ))->toArray() : []
            ))->toArray()
            : [];

        $tips = $recipe->relationLoaded('tips')
            ? $recipe->tips->map(fn($t) => new RecipeTipData(
                description: $t->description,
                relatedStepNumber: $t->recipe_step_id,
                startTimeInSeconds: $t->start_time_in_seconds
            ))->toArray()
            : [];

        $dish = $recipe->relationLoaded('dish') && $recipe->dish
            ? DishData::fromModel($recipe->dish)
            : null;

        $video = $recipe->relationLoaded('video') && $recipe->video
            ? VideoData::fromModel($recipe->video)
            : null;

        return new self(
            id: $recipe->id,
            title: $recipe->title,
            slug: $recipe->slug,
            summary: $recipe->summary,
            servingSize: $recipe->serving_size,
            cookingTime: $recipe->cooking_time,
            ingredients: $ingredients,
            steps: $steps,
            tips: $tips,
            dish: $dish,
            video: $video,
        );
    }
}
