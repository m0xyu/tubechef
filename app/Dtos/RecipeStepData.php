<?php

declare(strict_types=1);

namespace App\Dtos;

final readonly class RecipeStepData
{
    public function __construct(
        public int $stepNumber,
        public string $description,
        public ?int $startTimeInSeconds = null,
        public ?int $endTimeInSeconds = null,
        /** @var \App\Dtos\RecipeTipData[] */
        public array $tips = [],
    ) {}
}
