<?php

declare(strict_types=1);

namespace App\Dtos;

final readonly class RecipeTipData
{
    public function __construct(
        public string $description,
        public ?int $relatedStepNumber = null,
        public ?int $startTimeInSeconds = null,
    ) {}
}
