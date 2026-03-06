<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property int $step_number
 * @property string $description
 * @property int|null $start_time_in_seconds
 * @property int|null $end_time_in_seconds
 * 
 */
class RecipeStepResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'step_number' => $this->step_number,
            'description' => $this->description,
            'start_time_in_seconds' => $this->start_time_in_seconds,
            'end_time_in_seconds' => $this->end_time_in_seconds,
            'tips' => RecipeTipResource::collection($this->whenLoaded('tips'))
        ];
    }
}
