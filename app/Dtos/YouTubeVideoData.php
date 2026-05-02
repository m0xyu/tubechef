<?php

namespace App\Dtos;

use App\ValueObjects\YouTubeVideoId;
use Illuminate\Support\Arr;

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
     *   video_id: string,
     *   title: string,
     *   channel_name: string|null,
     *   channel_id: string,
     *   category_id: int|null,
     *   description: string|null,
     *   thumbnail_url: string|null,
     *   published_at: string|null,
     *   duration: int,
     *   view_count: int|null,
     *   like_count: int|null,
     *   comment_count: int|null,
     *   topic_categories: array<string>
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

    public static function fromApiResponse(YouTubeVideoId $videoId, array $item): self
    {
        $snippet = $item['snippet'] ?? [];
        $details = $item['contentDetails'] ?? [];
        $statistics = $item['statistics'] ?? [];
        $topicDetails = $item['topicDetails'] ?? [];

        $thumbHigh = Arr::get($snippet, 'thumbnails.high.url');
        $thumbDefault = Arr::get($snippet, 'thumbnails.default.url');

        $topicCategoriesRaw = Arr::get($topicDetails, 'topicCategories', []);
        $topicCategories = is_array($topicCategoriesRaw) ? array_values(array_filter($topicCategoriesRaw, 'is_string')) : [];

        // 厳格な型チェックとキャストを実行してインスタンス化
        return self::fromArray([
            'video_id' => (string)$videoId,
            'title' => (string)Arr::get($snippet, 'title', ''),
            'channel_name' => Arr::get($snippet, 'channelTitle'),
            'channel_id' => (string)Arr::get($snippet, 'channelId', ''),
            'category_id' => (int)Arr::get($snippet, 'categoryId', 0),
            'description' => Arr::get($snippet, 'description'),
            'thumbnail_url' => is_string($thumbHigh) ? $thumbHigh : (is_string($thumbDefault) ? $thumbDefault : null),
            'published_at' => Arr::get($snippet, 'publishedAt'),
            'duration' => self::convertDurationToSeconds(Arr::get($details, 'duration')),
            'view_count' => (int)Arr::get($statistics, 'viewCount', 0),
            'like_count' => (int)Arr::get($statistics, 'likeCount', 0),
            'comment_count' => (int)Arr::get($statistics, 'commentCount', 0),
            'topic_categories' => self::extractTopicNames($topicCategories),
        ]);
    }

    /**
     * DTOの内部ロジックとして隠蔽する
     */
    private static function extractTopicNames(array $urls): array
    {
        return array_map(function ($url) {
            return str_replace('_', ' ', urldecode(basename($url)));
        }, $urls);
    }

    private static function convertDurationToSeconds(mixed $isoDuration): int
    {
        if (!is_string($isoDuration)) return 0;
        try {
            $interval = new \DateInterval($isoDuration);
            return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
