<?php

namespace App\Dtos;

/**
 * @param string $name
 * @param string|null $quantity
 * @param string|null $group
 * @param integer $order
 */
final readonly class IngredientData
{

    public function __construct(
        public string $name,
        public ?string $quantity = null,
        public ?string $group = null,
        public int $order = 0,
    ) {}
}
