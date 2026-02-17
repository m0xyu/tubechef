<?php

namespace App\Actions;

use App\Dtos\YouTubeFullMetadataData;
use App\Enums\Errors\VideoError;
use App\Exceptions\VideoException;
use App\Models\Channel;
use App\Models\Video;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class YouTubeMetadataStoreAction
{
    /**
     * YouTube動画のメタデータを保存する
     * @param YouTubeFullMetadataData $metadata
     * @return Video
     * @throws VideoException
     */
    public function execute(YouTubeFullMetadataData $metadata): Video
    {
        try {
            return DB::transaction(function () use ($metadata) {
                // チャンネル情報の保存
                $channel = Channel::updateOrCreate(
                    ['channel_id' => $metadata->video->channelId],
                    [
                        'name' => $metadata->video->channelName,
                        'description' => $metadata->channel->channelDescription,
                        'thumbnail_url' => $metadata->channel->channelThumbnailUrl,
                        'custom_url' => $metadata->channel->channelCustomUrl,
                        'subscriber_count' => $metadata->channel->subscriberCount,
                        'view_count' => $metadata->channel->channelViewCount,
                        'video_count' => $metadata->channel->channelVideoCount,
                    ]
                );

                // 動画情報の保存
                $video = Video::updateOrCreate(
                    ['video_id' => $metadata->video->videoId],
                    [
                        'channel_id' => $channel->id,
                        'title' => $metadata->video->title,
                        'description' => $metadata->video->description,
                        'thumbnail_url' => $metadata->video->thumbnailUrl,
                        'category_id' => $metadata->video->categoryId,
                        'published_at' => $metadata->video->publishedAt,
                        'view_count' => $metadata->video->viewCount,
                        'duration' => $metadata->video->durationSeconds,
                        'like_count' => $metadata->video->likeCount,
                        'comment_count' => $metadata->video->commentCount,
                        'topic_categories' => $metadata->video->topicCategories,
                        'fetched_at' => now(),
                    ]
                );

                return $video;
            });
        } catch (Throwable $e) {
            Log::error('動画データの保存に失敗しました: ' . $e->getMessage(), [
                'video_id' => $metadata->video->videoId ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            throw new VideoException(VideoError::INTERNAL_ERROR);
        }
    }
}
