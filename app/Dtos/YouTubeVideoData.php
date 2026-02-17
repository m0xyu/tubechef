<?php

namespace App\Dtos;

final readonly class YouTubeVideoData
{
    /**
     * @param string $videoId
     * @param string $title
     * @param string|null $channelName
     * @param string $channelId
     * @param integer|null $categoryId
     * @param string|null $description
     * @param string|null $thumbnailUrl
     * @param string|null $publishedAt
     * @param integer $durationSeconds
     * @param integer|null $viewCount
     * @param integer|null $likeCount
     * @param integer|null $commentCount
     * @param array<string> $topicCategories
     */
    public function __construct(
        public string $videoId,
        public string $title,
        public ?string $channelName,
        public string $channelId,
        public ?int $categoryId,
        public ?string $description,
        public ?string $thumbnailUrl,
        public ?string $publishedAt,
        public int $durationSeconds,
        public ?int $viewCount,
        public ?int $likeCount,
        public ?int $commentCount,
        public array $topicCategories,
    ) {}

    /**
     * Action内の配列からインスタンスを生成する静的メソッド
     * @param array{
     * video_id: string,
     * title: string,
     * channel_name: string|null,
     * channel_id: string,
     * category_id: int|null,
     * description: string|null,
     * thumbnail_url: string|null,
     * published_at: string|null,
     * duration: int,
     * view_count: int|null,
     * like_count: int|null,
     * comment_count: int|null,
     * topic_categories: array<string>
     * } $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            videoId: $data['video_id'],
            title: $data['title'],
            channelName: $data['channel_name'] ?? null,
            channelId: $data['channel_id'],
            categoryId: $data['category_id'] ?? null,
            description: $data['description'] ?? null,
            thumbnailUrl: $data['thumbnail_url'] ?? null,
            publishedAt: $data['published_at'] ?? null,
            durationSeconds: (int) $data['duration'],
            viewCount: isset($data['view_count']) ? (int) $data['view_count'] : null,
            likeCount: isset($data['like_count']) ? (int) $data['like_count'] : null,
            commentCount: isset($data['comment_count']) ? (int) $data['comment_count'] : null,
            topicCategories: $data['topic_categories'],
        );
    }
}
