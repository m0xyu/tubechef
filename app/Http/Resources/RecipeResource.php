<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $summary
 * @property string|null $cooking_time
 * @property string|null $serving_size
 * @property-read \App\Models\Video|null $video
 */
class RecipeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'cooking_time' => $this->cookingTime,
            'serving_size' => $this->servingSize,
            'ingredients' => RecipeIngredientResource::collection($this->ingredients),
            'steps' => RecipeStepResource::collection($this->steps),
            'tips' => RecipeTipResource::collection($this->tips),
            'dish' => new DishResource($this->dish),
            'video' => new VideoResource($this->video),
            'channel' => $this->video?->channel
                ? new ChannelResource($this->video->channel)
                : null,
        ];
    }
}
