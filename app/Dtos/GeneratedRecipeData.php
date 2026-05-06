<?php

namespace App\Dtos;

final readonly class GeneratedRecipeData
{
    /**
     * @param array<IngredientData> $ingredients
     * @param array<RecipeStepData> $steps
     * @param array<RecipeTipData> $tips
     */
    public function __construct(
        public bool $isRecipe,
        public string $title,
        public string $summary,
        public string $dishName,
        public string $dishSlug,
        public array $ingredients,
        public array $steps,
        public array $tips = [],
        public ?string $servingSize = null,
        public ?string $cookingTime = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $ingredientsRaw = is_array($data['ingredients'] ?? null) ? $data['ingredients'] : [];
        $stepsRaw = is_array($data['steps'] ?? null) ? $data['steps'] : [];
        $tipsRaw = is_array($data['tips'] ?? null) ? $data['tips'] : [];

        $isRecipe = is_bool($data['is_recipe'] ?? null) ? $data['is_recipe'] : false;
        $title = is_string($data['title'] ?? null) ? $data['title'] : '';
        $summary = is_string($data['summary'] ?? null) ? $data['summary'] : '';
        $dishName = is_string($data['dish_name'] ?? null) ? $data['dish_name'] : '';
        $dishSlug = is_string($data['dish_slug'] ?? null) ? $data['dish_slug'] : '';
        $servingSize = is_string($data['serving_size'] ?? null) ? $data['serving_size'] : null;
        $cookingTime = is_string($data['cooking_time'] ?? null) ? $data['cooking_time'] : null;

        return new self(
            isRecipe: $isRecipe,
            title: $title,
            summary: $summary,
            dishName: $dishName,
            dishSlug: $dishSlug,
            ingredients: array_map(
                function (mixed $i): IngredientData {
                    $item = is_array($i) ? $i : [];
                    return new IngredientData(
                        name: is_string($item['name'] ?? null) ? $item['name'] : '',
                        quantity: is_string($item['quantity'] ?? null) ? $item['quantity'] : null,
                        group: is_string($item['group'] ?? null) ? $item['group'] : null,
                        order: is_numeric($item['order'] ?? null) ? (int) $item['order'] : 0
                    );
                },
                $ingredientsRaw
            ),
            steps: array_map(
                function (mixed $s): RecipeStepData {
                    $item = is_array($s) ? $s : [];
                    return new RecipeStepData(
                        stepNumber: is_numeric($item['step_number'] ?? null) ? (int) $item['step_number'] : 0,
                        description: is_string($item['description'] ?? null) ? $item['description'] : '',
                        startTimeInSeconds: is_numeric($item['start_time_in_seconds'] ?? null) ? (int) $item['start_time_in_seconds'] : null,
                        endTimeInSeconds: is_numeric($item['end_time_in_seconds'] ?? null) ? (int) $item['end_time_in_seconds'] : null
                    );
                },
                $stepsRaw
            ),
            tips: array_map(
                function (mixed $t): RecipeTipData {
                    $item = is_array($t) ? $t : [];
                    return new RecipeTipData(
                        description: is_string($item['description'] ?? null) ? $item['description'] : '',
                        relatedStepNumber: is_numeric($item['related_step_number'] ?? null) ? (int) $item['related_step_number'] : null,
                        startTimeInSeconds: is_numeric($item['start_time_in_seconds'] ?? null) ? (int) $item['start_time_in_seconds'] : null
                    );
                },
                $tipsRaw
            ),
            servingSize: $servingSize,
            cookingTime: $cookingTime,
        );
    }
}
