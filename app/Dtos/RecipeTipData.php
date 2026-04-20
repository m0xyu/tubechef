<?php

namespace App\Dtos;

/**
 * @param string $description
 * @param integer|null $relatedStepNumber
 * @param integer|null $startTimeInSeconds
 */
final readonly class RecipeTipData
{
    public function __construct(
        public string $description,
        public ?int $relatedStepNumber = null,
        public ?int $startTimeInSeconds = null,
    ) {}
}
