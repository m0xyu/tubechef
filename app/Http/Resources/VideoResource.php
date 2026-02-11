<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $video_id
 * @property string $title
 * @property string|null $description
 * @property string $url
 * @property int|null $category_id
 * @property string|null $thumbnail_url
 * @property int|null $duration
 * @property string $published_at
 * @property string $recipe_generation_status
 * @property string|null $recipe_generation_status_message
 * @property int|null $view_count
 * @property int|null $like_count
 * @property int|null $comment_count
 * @property array<string>|null $topic_categories
 * @property-read \App\Models\Channel $channel
 * @property-read \App\Models\Recipe|null $recipe
 */
class VideoResource extends JsonResource
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
            'video_id' => $this->video_id,
            'title' => $this->title,
            'description' => $this->description,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnail_url,
            'duration' => $this->duration,
            'published_at' => $this->published_at,
            'channel' => new ChannelResource($this->whenLoaded('channel')),
            'recipe_generation_status' => $this->recipe_generation_status,
            'recipe_generation_status_message' => $this->recipe_generation_status_message,
            'view_count' => $this->view_count,
            'like_count' => $this->like_count,
            'comment_count' => $this->comment_count,
            'topic_categories' => $this->topic_categories,
            'recipe_slug' => $this->resource->relationLoaded('recipe') && $this->recipe
                ? $this->recipe->slug
                : null,
        ];
    }
}
