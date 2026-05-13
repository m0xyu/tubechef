<?php

declare(strict_types=1);

namespace App\Dtos;

final readonly class GeneratedRecipeData
{
    public function __construct(
        public bool $isRecipe,
        public string $title,
        public string $summary,
        public string $dishName,
        public string $dishSlug,
        /** @var \App\Dtos\IngredientData[] */
        public array $ingredients,
        /** @var \App\Dtos\RecipeStepData[] */
        public array $steps = [],
        /** @var \App\Dtos\RecipeTipData[] */
        public array $tips = [],
        public ?string $servingSize = null,
        public ?string $cookingTime = null,
    ) {}
}
