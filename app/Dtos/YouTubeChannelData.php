<?php

namespace App\Dtos;

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
     * Action内の配列からインスタンスを生成する静的メソッド
     * @param array{
     * channel_description: string|null,
     * channel_custom_url: string|null,
     * channel_thumbnail_url: string|null,
     * subscriber_count: int|string|null,
     * channel_view_count: int|string|null,
     * channel_video_count: int|string|null
     * } $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            channelDescription: $data['channel_description'] ?? null,
            channelCustomUrl: $data['channel_custom_url'] ?? null,
            channelThumbnailUrl: $data['channel_thumbnail_url'] ?? null,
            subscriberCount: $data['subscriber_count'] ?? null,
            channelViewCount: $data['channel_view_count'] ?? null,
            channelVideoCount: $data['channel_video_count'] ?? null,
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
