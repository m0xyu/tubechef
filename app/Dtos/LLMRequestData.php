<?php

namespace App\Dtos;

use App\Models\Video;

/**
 * LLMサービスへの入力データを表すDTO
 */
final readonly class LLMRequestData
{
    public function __construct(
        public string $videoId,
        public string $title,
        public string $description,
        public ?int $duration,
        public string $videoUrl,
    ) {}

    public static function fromVideo(Video $video): self
    {
        return new self(
            videoId: $video->video_id,
            title: $video->title,
            description: $video->description ?? '',
            duration: $video->duration,
            videoUrl: $video->url,
        );
    }
}
