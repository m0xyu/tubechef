<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $title
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
            'summary' => $this->summary,
            'cooking_time' => $this->cooking_time,
            'serving_size' => $this->serving_size,
            'ingredients' => RecipeIngredientResource::collection($this->whenLoaded('ingredients')),
            'steps' => RecipeStepResource::collection($this->whenLoaded('steps')),
            'tips' => RecipeTipResource::collection($this->whenLoaded('tips')),
            'dish' => new DishResource($this->whenLoaded('dish')),
            'video' => new VideoResource($this->whenLoaded('video')),
            'channel' => $this->video?->channel
                ? new ChannelResource($this->video->channel)
                : null,
        ];
    }
}
