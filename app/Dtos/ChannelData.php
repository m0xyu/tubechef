<?php

namespace App\Dtos;

use App\Models\Channel;


final readonly class ChannelData
{
    /**
     * @param integer $id
     * @param string $channelId
     * @param string $name
     * @param string|null $description
     * @param string|null $thumbnailUrl
     * @param string|null $customUrl
     * @param integer|null $viewCount
     * @param integer|null $subscriberCount
     * @param integer|null $videoCount
     * @param \DateTimeImmutable $createdAt
     * @param \DateTimeImmutable $updatedAt
     */
    public function __construct(
        public int $id,
        public string $channelId,
        public string $name,
        public ?string $description,
        public ?string $thumbnailUrl,
        public ?string $customUrl,
        public ?int $viewCount,
        public ?int $subscriberCount,
        public ?int $videoCount,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {}

    /**
     * ChannelモデルからDTOを生成する
     */
    public static function fromModel(Channel $channel): self
    {
        return new self(
            id: $channel->id,
            channelId: $channel->channel_id,
            name: $channel->name,
            description: $channel->description,
            thumbnailUrl: $channel->thumbnail_url,
            customUrl: $channel->custom_url,
            viewCount: $channel->view_count,
            subscriberCount: $channel->subscriber_count,
            videoCount: $channel->video_count,
            createdAt: $channel->created_at->toDateTimeImmutable(),
            updatedAt: $channel->updated_at->toDateTimeImmutable(),
        );
    }
}
