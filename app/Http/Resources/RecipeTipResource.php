<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $description
 * @property int|null $related_step_number
 * @property int|null $start_time_in_seconds
 * 
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
            'related_step' => $this->related_step_number,
            'start_time_in_seconds' => $this->start_time_in_seconds,
        ];
    }
}
