<?php

namespace App\Dtos;

/**
 * @param integer $id
 * @param integer $stepNumber
 * @param string $description
 * @param integer|null $startTimeInSeconds
 * @param integer|null $endTimeInSeconds
 * @param array<RecipeTipData> $tips
 */
final readonly class RecipeStepData
{
    public function __construct(
        public int $id,
        public int $stepNumber,
        public string $description,
        public ?int $startTimeInSeconds = null,
        public ?int $endTimeInSeconds = null,
        public array $tips = [],
    ) {}
}
