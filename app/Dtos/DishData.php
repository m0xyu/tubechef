<?php

namespace App\Dtos;

use App\Models\Dish;

/**
 * @param int $id
 * @param string $name
 * @param string $slug
 * @param string|null $description
 * @param \DateTimeImmutable $createdAt
 */
final readonly class DishData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public \DateTimeImmutable $createdAt,
    ) {}

    /**
     * DishモデルからDTOを生成する
     */
    public static function fromModel(Dish $dish): self
    {
        return new self(
            id: $dish->id,
            name: $dish->name,
            slug: $dish->slug,
            description: $dish->description,
            createdAt: $dish->created_at->toDateTimeImmutable()
        );
    }
}
