<?php

namespace App\Dtos;

final readonly class IngredientData
{
    /**
     * @param string $name
     * @param string|null $quantity
     * @param string|null $group
     * @param integer $order
     */
    public function __construct(
        public string $name,
        public ?string $quantity = null,
        public ?string $group = null,
        public int $order = 0,
    ) {}
}
