<?php

declare(strict_types=1);

namespace App\Dtos;

use Illuminate\Support\Arr;

final readonly class YouTubeChannelData
{
    public function __construct(
        public ?string $channelDescription = null,
        public ?string $channelCustomUrl = null,
        public ?string $channelThumbnailUrl = null,
        public ?int $subscriberCount = null,
        public ?int $channelViewCount = null,
        public ?int $channelVideoCount = null,
    ) {}

    /**
     * YouTube APIの生レスポンスからDTOを生成する
     * @param array<string, mixed> $item
     * @return self
     */
    public static function fromApiResponse(array $item): self
    {
        // 💡 Arr::get() のドット記法で深い階層から直接取得
        $description = Arr::get($item, 'snippet.description');
        $customUrl = Arr::get($item, 'snippet.customUrl');
        $thumbHigh = Arr::get($item, 'snippet.thumbnails.high.url');
        $thumbDefault = Arr::get($item, 'snippet.thumbnails.default.url');

        $subscriberCount = Arr::get($item, 'statistics.subscriberCount');
        $viewCount = Arr::get($item, 'statistics.viewCount');
        $videoCount = Arr::get($item, 'statistics.videoCount');

        return new self(
            channelDescription: is_string($description) ? $description : null,
            channelCustomUrl: is_string($customUrl) ? $customUrl : null,
            channelThumbnailUrl: is_string($thumbHigh) ? $thumbHigh : (is_string($thumbDefault) ? $thumbDefault : null),
            subscriberCount: is_numeric($subscriberCount) ? (int)$subscriberCount : null,
            channelViewCount: is_numeric($viewCount) ? (int)$viewCount : null,
            channelVideoCount: is_numeric($videoCount) ? (int)$videoCount : null,
        );
    }

    /**
     * 空のYouTubeChannelDataを返す
     * @return self
     */
    public static function empty(): self
    {
        return new self();
    }
}
