<?php

namespace App\Actions;

use App\Models\Video;

class VideoMetadataUpdateAction
{
    /**
     * AI生成に関連するメタデータを一括更新する
     * 
     * @param \App\Models\Video $video
     * @param array<string> $metadata
     * @return void
     */
    public function execute(Video $video, array $metadata): void
    {
        $video->update([
            'ai_metadata' => $metadata,
        ]);
    }
}
