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
        return new self(
            isRecipe: (bool) ($data['is_recipe'] ?? false),
            title: (string) ($data['title'] ?? ''),
            summary: (string) ($data['summary'] ?? ''),
            dishName: (string) ($data['dish_name'] ?? ''),
            dishSlug: (string) ($data['dish_slug'] ?? ''),
            ingredients: array_map(
                fn(array $i) => new IngredientData(
                    name: $i['name'],
                    quantity: $i['quantity'] ?? null,
                    group: $i['group'] ?? null,
                    order: (int) ($i['order'] ?? 0)
                ),
                $data['ingredients'] ?? []
            ),
            steps: array_map(
                fn(array $s) => new RecipeStepData(
                    stepNumber: (int) $s['step_number'],
                    description: $s['description'],
                    startTimeInSeconds: isset($s['start_time_in_seconds']) ? (int) $s['start_time_in_seconds'] : null,
                    endTimeInSeconds: isset($s['end_time_in_seconds']) ? (int) $s['end_time_in_seconds'] : null
                ),
                $data['steps'] ?? []
            ),
            tips: array_map(
                fn(array $t) => new RecipeTipData(
                    description: $t['description'],
                    relatedStepNumber: isset($t['related_step_number']) ? (int) $t['related_step_number'] : null,
                    startTimeInSeconds: isset($t['start_time_in_seconds']) ? (int) $t['start_time_in_seconds'] : null
                ),
                $data['tips'] ?? []
            ),
            servingSize: $data['serving_size'] ?? null,
            cookingTime: $data['cooking_time'] ?? null,
        );
    }
}
