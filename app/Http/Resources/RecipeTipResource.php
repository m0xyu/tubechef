<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $description
 * @property int|null $recipe_step_id
 * @property int|null $start_time_in_seconds
 */
class RecipeTipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'description' => $this->description,
            'related_step' => $this->recipe_step_id,
            'start_time_in_seconds' => $this->start_time_in_seconds,
        ];
    }
}
