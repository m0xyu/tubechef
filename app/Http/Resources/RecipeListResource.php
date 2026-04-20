<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property int|null $cooking_time
 * @property string $title
 * @property string $slug
 * @property string $thumbnail_url
 * @property string $channel_name
 * @property \App\Models\Video|null $video
 * @property DishResource|null $dish
 */
class RecipeListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'slug'          => $this->slug,
            'thumbnail_url' => $this->thumbnailUrl,
            'cooking_time'  => $this->cookingTime,
            'channel_name'  => $this->channelName,
            'dish' => [
                'name' => $this->dishName,
            ],
        ];
    }
}
