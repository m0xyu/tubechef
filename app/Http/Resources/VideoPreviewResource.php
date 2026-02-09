<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $video_id
 * @property string $title
 * @property string $description
 * @property string $thumbnail_url
 * @property int|null $duration
 * @property string $published_at
 * @property string $channel_name
 * @property string $channel_id
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
        return [
            'video_id' => $this->video_id,
            'title' => $this->title,
            'description' => $this->description,
            'thumbnail_url' => $this->thumbnail_url,
            'duration' => $this->duration,
            'published_at' => $this->published_at,
            'channel' => [
                'name' => $this->channel_name,
                'id' => $this->channel_id,
            ],
        ];
    }
}
