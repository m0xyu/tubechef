<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $step_number
 * @property string $description
 * @property int|null $start_time_in_seconds
 * @property int|null $end_time_in_seconds
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\RecipeTip[] $tips
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
            'step_number' => $this->stepNumber,
            'description' => $this->description,
            'start_time_in_seconds' => $this->startTimeInSeconds,
            'end_time_in_seconds' => $this->endTimeInSeconds,
            'tips' => RecipeTipResource::collection($this->tips),
        ];
    }
}
