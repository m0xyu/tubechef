<?php

namespace App\Dtos;

use App\Models\Recipe;

/**
 * @param int $id
 * @param string $title
 * @param string $slug
 * @param string|null $thumbnailUrl
 * @param string|null $channelName
 * @param string|null $cookingTime
 * @param string|null $dishName
 */
final readonly class RecipeListData
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public ?string $thumbnailUrl,
        public ?string $channelName,
        public ?string $cookingTime,
        public ?string $dishName,
    ) {}

    /**
     * Eloquentモデルから一覧用DTOを生成する
     */
    public static function fromModel(Recipe $recipe): self
    {
        return new self(
            id: $recipe->id,
            title: $recipe->title,
            slug: $recipe->slug,
            thumbnailUrl: $recipe->relationLoaded('video') ? $recipe->video?->thumbnail_url : null,
            channelName: ($recipe->relationLoaded('video') && $recipe->video?->relationLoaded('channel'))
                ? $recipe->video->channel?->name
                : null,
            cookingTime: $recipe->cooking_time,
            dishName: $recipe->relationLoaded('dish') ? $recipe->dish?->name : null,
        );
    }
}
