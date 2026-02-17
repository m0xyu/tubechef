<?php

namespace App\Dtos;

final readonly class RecipeTipData
{
    /**
     * @param string $description
     * @param integer|null $relatedStepNumber
     * @param integer|null $startTimeInSeconds
     */
    public function __construct(
        public string $description,
        public ?int $relatedStepNumber = null,
        public ?int $startTimeInSeconds = null,
    ) {}
}
