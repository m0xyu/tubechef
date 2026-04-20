<?php

namespace App\Dtos;

use App\Enums\RecipeGenerationStatus;
use App\Models\Video;

/**
 * Undocumented function
 *
 * @param integer $id
 * @param integer $channelId
 * @param ChannelData|null $channel
 * @param string $videoId
 * @param string $title
 * @param string|null $description
 * @param string|null $thumbnailUrl
 * @param \DateTimeImmutable $publishedAt
 * @param integer|null $duration
 * @param integer|null $viewCount
 * @param integer|null $likeCount
 * @param integer|null $commentCount
 * @param array $topicCategories
 * @param string|null $categoryId
 * @param RecipeGenerationStatus $recipeGenerationStatus
 * @param string|null $recipeGenerationStatusMessage
 * @param integer $generationRetryCount
 * @param array $aiMetadata
 * @param \DateTimeImmutable $createdAt
 * @param \DateTimeImmutable $updatedAt
 * @param \DateTimeImmutable $fetchedAt
 */
final readonly class VideoData
{

    public function __construct(
        public int $id,
        public int $channelId,
        public ?ChannelData $channel = null,
        public string $videoId,
        public string $title,
        public ?string $description,
        public ?string $thumbnailUrl,
        public \DateTimeImmutable $publishedAt,
        public ?int $duration,
        public ?int $viewCount,
        public ?int $likeCount,
        public ?int $commentCount,
        public array $topicCategories,
        public ?string $categoryId,
        public RecipeGenerationStatus $recipeGenerationStatus,
        public ?string $recipeGenerationStatusMessage,
        public int $generationRetryCount,
        public array $aiMetadata,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
        public \DateTimeImmutable $fetchedAt,
    ) {}

    /**
     * EloquentモデルからDTOを生成する
     */
    public static function fromModel(Video $video): self
    {

        $channel = $video->relationLoaded('channel') && $video->channel
            ? ChannelData::fromModel($video->channel)
            : null;

        return new self(
            id: $video->id,
            channelId: $video->channel_id,
            channel: $channel,
            videoId: $video->video_id,
            title: $video->title,
            description: $video->description,
            thumbnailUrl: $video->thumbnail_url,
            publishedAt: $video->published_at->toDateTimeImmutable(),
            duration: $video->duration,
            viewCount: $video->view_count,
            likeCount: $video->like_count,
            commentCount: $video->comment_count,
            topicCategories: $video->topic_categories ?? [],
            categoryId: $video->category_id,
            recipeGenerationStatus: $video->recipe_generation_status,
            recipeGenerationStatusMessage: $video->recipe_generation_error_message,
            generationRetryCount: $video->generation_retry_count,
            aiMetadata: $video->ai_metadata ?? [],
            createdAt: $video->created_at->toDateTimeImmutable(),
            updatedAt: $video->updated_at->toDateTimeImmutable(),
            fetchedAt: $video->fetched_at->toDateTimeImmutable(),
        );
    }
}
