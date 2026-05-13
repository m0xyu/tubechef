<?php

declare(strict_types=1);

namespace App\Dtos;

final readonly class IngredientData
{
    public function __construct(
        public string $name,
        public ?string $quantity = null,
        public ?string $group = null,
        public int $order = 0,
    ) {}
}
