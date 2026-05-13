<?php

declare(strict_types=1);

namespace App\Dtos;

use App\ValueObjects\YouTubeVideoId;
use Illuminate\Support\Arr;

final readonly class YouTubeVideoData
{
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
        /** @var array<string> */
        public array $topicCategories,
    ) {}

    /**
     * APIの生レスポンスからDTOを生成する
     * @param array<string, mixed> $item
     * @return self
     */
    public static function fromApiResponse(YouTubeVideoId $videoId, array $item): self
    {
        $title = Arr::get($item, 'snippet.title');
        $channelTitle = Arr::get($item, 'snippet.channelTitle');
        $channelId = Arr::get($item, 'snippet.channelId');
        $categoryId = Arr::get($item, 'snippet.categoryId');
        $description = Arr::get($item, 'snippet.description');
        $publishedAt = Arr::get($item, 'snippet.publishedAt');

        $thumbHigh = Arr::get($item, 'snippet.thumbnails.high.url');
        $thumbDefault = Arr::get($item, 'snippet.thumbnails.default.url');
        $thumbnailUrl = is_string($thumbHigh) ? $thumbHigh : (is_string($thumbDefault) ? $thumbDefault : null);

        $duration = Arr::get($item, 'contentDetails.duration');
        $viewCount = Arr::get($item, 'statistics.viewCount');
        $likeCount = Arr::get($item, 'statistics.likeCount');
        $commentCount = Arr::get($item, 'statistics.commentCount');

        $topicCategoriesRaw = Arr::get($item, 'topicDetails.topicCategories', []);
        $topicCategoriesUrls = is_array($topicCategoriesRaw) ? array_values(array_filter($topicCategoriesRaw, 'is_string')) : [];

        return new self(
            videoId: (string)$videoId,
            title: is_string($title) ? $title : '',
            channelName: is_string($channelTitle) ? $channelTitle : null,
            channelId: is_string($channelId) ? $channelId : '',
            categoryId: is_string($categoryId) ? $categoryId : null,
            description: is_string($description) ? $description : null,
            thumbnailUrl: $thumbnailUrl,
            publishedAt: is_string($publishedAt) ? $publishedAt : null,
            durationSeconds: self::convertDurationToSeconds($duration),
            viewCount: is_numeric($viewCount) ? (int)$viewCount : null,
            likeCount: is_numeric($likeCount) ? (int)$likeCount : null,
            commentCount: is_numeric($commentCount) ? (int)$commentCount : null,
            topicCategories: self::extractTopicNames($topicCategoriesUrls),
        );
    }

    /**
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
