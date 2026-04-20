<?php

namespace App\Dtos;

/**
 * @param integer $stepNumber
 * @param string $description
 * @param integer|null $startTimeInSeconds
 * @param integer|null $endTimeInSeconds
 */
final readonly class RecipeStepData
{
    public function __construct(
        public int $stepNumber,
        public string $description,
        public ?int $startTimeInSeconds = null,
        public ?int $endTimeInSeconds = null,
    ) {}
}
