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
     * @param string|null $categoryId
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
        public ?string $categoryId,
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
     *   category_id: string|null,
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
            durationSeconds: $data['duration'],
            viewCount: $data['view_count'] ?? null,
            likeCount: $data['like_count'] ?? null,
            commentCount: $data['comment_count'] ?? null,
            topicCategories: $data['topic_categories'],
        );
    }

    /**
     * APIの生レスポンスからDTOを生成する
     * @param array<string, mixed> $item
     * @return self
     */
    public static function fromApiResponse(YouTubeVideoId $videoId, array $item): self
    {
        // 💡 修正1: 確実に配列であることを保証してから Arr::get に渡す
        $snippet = is_array($item['snippet'] ?? null) ? $item['snippet'] : [];
        $details = is_array($item['contentDetails'] ?? null) ? $item['contentDetails'] : [];
        $statistics = is_array($item['statistics'] ?? null) ? $item['statistics'] : [];
        $topicDetails = is_array($item['topicDetails'] ?? null) ? $item['topicDetails'] : [];

        $thumbHigh = Arr::get($snippet, 'thumbnails.high.url');
        $thumbDefault = Arr::get($snippet, 'thumbnails.default.url');
        $thumbnailUrl = is_string($thumbHigh) ? $thumbHigh : (is_string($thumbDefault) ? $thumbDefault : null);

        $topicCategoriesRaw = Arr::get($topicDetails, 'topicCategories', []);
        $topicCategories = is_array($topicCategoriesRaw) ? array_values(array_filter($topicCategoriesRaw, 'is_string')) : [];

        $title = Arr::get($snippet, 'title');
        $channelTitle = Arr::get($snippet, 'channelTitle');
        $channelId = Arr::get($snippet, 'channelId');
        $categoryId = Arr::get($snippet, 'categoryId');
        $description = Arr::get($snippet, 'description');
        $publishedAt = Arr::get($snippet, 'publishedAt');

        $duration = Arr::get($details, 'duration');
        $viewCount = Arr::get($statistics, 'viewCount');
        $likeCount = Arr::get($statistics, 'likeCount');
        $commentCount = Arr::get($statistics, 'commentCount');

        return self::fromArray([
            'video_id' => (string)$videoId,
            'title' => is_string($title) ? $title : '',
            'channel_name' => is_string($channelTitle) ? $channelTitle : null,
            'channel_id' => is_string($channelId) ? $channelId : '',
            'category_id' => is_string($categoryId) ? $categoryId : null,
            'description' => is_string($description) ? $description : null,
            'thumbnail_url' => $thumbnailUrl,
            'published_at' => is_string($publishedAt) ? $publishedAt : null,
            'duration' => self::convertDurationToSeconds($duration),
            'view_count' => is_numeric($viewCount) ? (int)$viewCount : null,
            'like_count' => is_numeric($likeCount) ? (int)$likeCount : null,
            'comment_count' => is_numeric($commentCount) ? (int)$commentCount : null,
            'topic_categories' => self::extractTopicNames($topicCategories),
        ]);
    }

    /**
     * DTOの内部ロジックとして隠蔽する
     * @param array<string> $urls
     * @return array<string>
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
