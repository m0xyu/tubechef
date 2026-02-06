<?php

namespace App\Actions;

use App\Models\Channel;
use App\Models\Video;
use Illuminate\Support\Facades\DB;

class YouTubeMetadataStoreAction
{
    /**
     * YouTube動画のメタデータを保存する
     * 
     * @param array<string, mixed> $metadata
     * @return Video
     */
    public function execute(array $metadata): Video
    {
        return DB::transaction(function () use ($metadata) {
            // チャンネル情報の保存
            $channel = Channel::updateOrCreate(
                ['channel_id' => $metadata['channel_id']],
                [
                    'name' => $metadata['channel_name'],
                    'thumbnail_url' => $metadata['channel_thumbnail_url'],
                    'custom_url' => $metadata['channel_custom_url'],
                    'subscriber_count' => $metadata['subscriber_count'],
                    'view_count' => $metadata['channel_view_count'],
                    'video_count' => $metadata['channel_video_count'],
                ]
            );

            // 動画情報の保存
            $video = Video::updateOrCreate(
                ['video_id' => $metadata['video_id']],
                [
                    'channel_id' => $channel->id,
                    'title' => $metadata['title'],
                    'description' => $metadata['description'],
                    'thumbnail_url' => $metadata['thumbnail_url'],
                    'published_at' => $metadata['published_at'],
                    'view_count' => $metadata['view_count'],
                    'duration' => $metadata['duration'],
                    'like_count' => $metadata['like_count'],
                    'comment_count' => $metadata['comment_count'],
                    'topic_categories' => $metadata['topic_categories'],
                    'fetched_at' => now(),
                ]
            );

            return $video;
        });
    }
}
