<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Video
 * @property string $video_id
 * @property string $title
 * @property string $description
 * @property string $thumbnail_url
 * @property int|null $duration
 * @property string $published_at
 * @property string $channel_name
 * @property string $channel_id
 * @property \App\Models\Channel|null $channel
 * @property \App\Enums\RecipeGenerationStatus|null $recipe_generation_status
 */
class VideoPreviewResource extends JsonResource
{
    /**
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $channelName = $this->channel ? $this->channel->name : ($this->channel_name ?? '');
        $channelId = $this->channel ? $this->channel->channel_id : ($this->channel_id ?? '');

        return [
            'video_id' => $this->video_id,
            'title' => $this->title,
            'description' => $this->description,
            'thumbnail_url' => $this->thumbnail_url,
            'duration' => $this->duration,
            'published_at' => $this->published_at,
            'channel' => [
                'name' => $channelName,
                'id' => $channelId,
            ],
            'is_registered' => $this->exists,
            'recipe_generation_status' => $this->recipe_generation_status ?? null,
        ];
    }
}
