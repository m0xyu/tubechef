<?php

namespace App\Dtos;

use App\Dtos\YouTubeChannelData;
use App\Dtos\YouTubeVideoData;

final readonly class YouTubeFullMetadataData
{
    /**
     * @param YouTubeVideoData $video
     * @param YouTubeChannelData $channel
     */
    public function __construct(
        public YouTubeVideoData $video,
        public YouTubeChannelData $channel
    ) {}
}
