<?php

namespace App\Dtos;

final readonly class RecipeStepData
{
    /**
     * @param integer $stepNumber
     * @param string $description
     * @param integer|null $startTimeInSeconds
     * @param integer|null $endTimeInSeconds
     */
    public function __construct(
        public int $stepNumber,
        public string $description,
        public ?int $startTimeInSeconds = null,
        public ?int $endTimeInSeconds = null,
    ) {}
}
