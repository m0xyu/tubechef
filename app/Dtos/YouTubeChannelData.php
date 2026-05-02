<?php

namespace App\Dtos;

use Illuminate\Support\Arr;

final readonly class YouTubeChannelData
{
    /**
     * @param string|null $channelDescription
     * @param string|null $channelCustomUrl
     * @param string|null $channelThumbnailUrl
     * @param integer|null $subscriberCount
     * @param integer|null $channelViewCount
     * @param integer|null $channelVideoCount
     */
    public function __construct(
        public ?string $channelDescription = null,
        public ?string $channelCustomUrl = null,
        public ?string $channelThumbnailUrl = null,
        public ?int $subscriberCount = null,
        public ?int $channelViewCount = null,
        public ?int $channelVideoCount = null,
    ) {}

    /**
     * APIの生レスポンスからDTOを生成するファクトリメソッド
     * 
     * @param array{kind: string, id?: string, snippet?: array<string, mixed>, statistics?: array<string, mixed>} $item
     * @return self
     */
    public static function fromApiResponse(array $item): self
    {
        $snippet = $item['snippet'] ?? [];
        $statistics = $item['statistics'] ?? [];

        $thumbHigh = Arr::get($snippet, 'thumbnails.high.url');
        $thumbDefault = Arr::get($snippet, 'thumbnails.default.url');

        // fromArrayの型キャスト処理を活かして安全に生成
        return self::fromArray([
            'channel_description' => Arr::get($snippet, 'description'),
            'channel_custom_url' => Arr::get($snippet, 'customUrl'),
            'channel_thumbnail_url' => is_string($thumbHigh) ? $thumbHigh : (is_string($thumbDefault) ? $thumbDefault : null),
            'subscriber_count' => Arr::get($statistics, 'subscriberCount'),
            'channel_view_count' => Arr::get($statistics, 'viewCount'),
            'channel_video_count' => Arr::get($statistics, 'videoCount'),
        ]);
    }

    /**
     * Action内の配列からインスタンスを生成する静的メソッド
     * @param array{
     * channel_description?: mixed,
     * channel_custom_url?: mixed,
     * channel_thumbnail_url?: mixed,
     * subscriber_count?: mixed,
     * channel_view_count?: mixed,
     * channel_video_count?: mixed
     * } $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            channelDescription: isset($data['channel_description']) && is_string($data['channel_description']) ? $data['channel_description'] : null,
            channelCustomUrl: isset($data['channel_custom_url']) && is_string($data['channel_custom_url']) ? $data['channel_custom_url'] : null,
            channelThumbnailUrl: isset($data['channel_thumbnail_url']) && is_string($data['channel_thumbnail_url']) ? $data['channel_thumbnail_url'] : null,
            subscriberCount: isset($data['subscriber_count']) && is_numeric($data['subscriber_count']) ? (int)$data['subscriber_count'] : null,
            channelViewCount: isset($data['channel_view_count']) && is_numeric($data['channel_view_count']) ? (int)$data['channel_view_count'] : null,
            channelVideoCount: isset($data['channel_video_count']) && is_numeric($data['channel_video_count']) ? (int)$data['channel_video_count'] : null,
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
