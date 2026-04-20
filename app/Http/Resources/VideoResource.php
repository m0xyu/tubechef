<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $videoId
 * @property string $title
 * @property string|null $description
 * @property string $url
 * @property int|null $categoryId
 * @property string|null $thumbnailUrl
 * @property int|null $duration
 * @property string $publishedAt
 * @property string $recipeGenerationStatus
 * @property string|null $recipeGenerationStatusMessage
 * @property int|null $viewCount
 * @property int|null $likeCount
 * @property int|null $commentCount
 * @property array<string>|null $topicCategories
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
            'video_id' => $this->videoId,
            'title' => $this->title,
            'description' => $this->description,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnailUrl,
            'duration' => $this->duration,
            'published_at' => $this->publishedAt,
            'channel' => new ChannelResource($this->channel),
            'recipe_generation_status' => $this->recipeGenerationStatus,
            'recipe_generation_status_message' => $this->recipeGenerationStatusMessage,
            'view_count' => $this->viewCount,
            'like_count' => $this->likeCount,
            'comment_count' => $this->commentCount,
            'topic_categories' => $this->topicCategories,
        ];
    }
}
