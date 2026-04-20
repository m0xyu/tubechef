<?php

namespace App\Dtos;

use App\Enums\RecipeGenerationStatus;
use App\Models\Video;


final readonly class VideoData
{
    /**
     *
     * @param integer $id
     * @param integer $channelId
     * @param ChannelData|null $channel
     * @param string $videoId
     * @param string $url
     * @param string $title
     * @param string|null $description
     * @param string|null $thumbnailUrl
     * @param \DateTimeImmutable $publishedAt
     * @param integer|null $duration
     * @param integer|null $viewCount
     * @param integer|null $likeCount
     * @param integer|null $commentCount
     * @param array<string> $topicCategories
     * @param string|null $categoryId
     * @param RecipeGenerationStatus $recipeGenerationStatus
     * @param string|null $recipeGenerationStatusMessage
     * @param integer $generationRetryCount
     * @param array<string, mixed> $aiMetadata
     * @param \DateTimeImmutable $createdAt
     * @param \DateTimeImmutable $updatedAt
     * @param \DateTimeImmutable $fetchedAt
     */
    public function __construct(
        public int $id,
        public int $channelId,
        public string $url,
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
        public ?ChannelData $channel = null,
    ) {}

    /**
     * EloquentモデルからDTOを生成する
     */
    public static function fromModel(Video $video): self
    {

        $channelModel = $video->relationLoaded('channel') ? $video->getRelation('channel') : null;
        $channel = $channelModel ? ChannelData::fromModel($channelModel) : null;

        return new self(
            id: $video->id,
            channelId: $video->channel_id,
            channel: $channel,
            url: $video->url,
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
