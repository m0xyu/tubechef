<?php

namespace App\Actions;

use App\Config\GeminiConfig;
use App\Dtos\YouTubeVideoData;
use App\Enums\Errors\VideoError;
use App\Models\Video;
use App\Enums\RecipeGenerationStatus;
use App\Exceptions\VideoException;
use App\Models\Channel;
use App\ValueObjects\YouTubeVideoId;
use Illuminate\Support\Facades\DB;

class GetVideoPreviewAction
{
    const VIDEO_SECOND_LIMIT = 60;
    public function __construct(
        private FetchYouTubeMetadataAction $fetchYouTubeMetadata
    ) {}

    /**
     * 動画のプレビュー用インスタンスを取得する
     * 不適格な動画はここで「最終失敗」として保存される
     * @param YouTubeVideoId $videoId
     * @return Video 
     */
    public function execute(YouTubeVideoId $videoId): Video
    {
        if ($video = Video::where('video_id', (string)$videoId)->with(['recipe'])->first()) {
            return $video;
        }

        $metadata = $this->fetchYouTubeMetadata->execute($videoId, FetchYouTubeMetadataAction::PARTS_PREVIEW);

        $invalidError = $this->getInvalidReason($metadata);

        if ($invalidError) {
            return $this->storeAsInvalid($metadata, $invalidError);
        }

        return $this->makeUnsavedVideo($metadata);
    }

    /**
     * 動画がレシピ生成に適しているか判定する
     * @param YouTubeVideoData $metadata
     * @return VideoError|null
     */
    private function getInvalidReason(YouTubeVideoData $metadata): ?VideoError
    {
        if (!in_array('Food', $metadata->topicCategories ?? [])) {
            return VideoError::NOT_A_FOOD_CATEGORY;
        }

        // 2. 短すぎる（Shorts相当）
        if ($metadata->durationSeconds <= self::VIDEO_SECOND_LIMIT) {
            return VideoError::VIDEO_TOO_SHORT;
        }

        return null;
    }

    /**
     * 新規動画の未保存インスタンスを生成する
     * @param YouTubeVideoData $metadata
     * @return Video
     */
    private function makeUnsavedVideo(YouTubeVideoData $metadata): Video
    {
        return (new Video())->forceFill([
            'video_id'         => $metadata->videoId,
            'title'            => $metadata->title,
            'channel_name'     => $metadata->channelName,
            'channel_id'       => $metadata->channelId,
            'category_id'      => $metadata->categoryId,
            'description'      => $metadata->description,
            'thumbnail_url'    => $metadata->thumbnailUrl,
            'published_at'     => $metadata->publishedAt,
            'duration'         => $metadata->durationSeconds,
            'topic_categories' => $metadata->topicCategories,
        ]);
    }

    /**
     * 不適格な動画を「生成不可」として永続化する
     * @param YouTubeVideoData $metadata
     * @param VideoError $error
     * @return Video
     */
    private function storeAsInvalid(YouTubeVideoData $metadata, VideoError $error): Video
    {
        return DB::transaction(function () use ($metadata, $error) {
            $channel = Channel::firstOrCreate(
                ['channel_id' => $metadata->channelId],
                ['name' => $metadata->channelName]
            );

            $maxRetryCount = GeminiConfig::retryCount();

            return Video::create([
                'video_id'         => $metadata->videoId,
                'title'            => $metadata->title,
                'channel_id'       => $channel->id,
                'category_id'      => $metadata->categoryId,
                'description'      => $metadata->description,
                'thumbnail_url'    => $metadata->thumbnailUrl,
                'published_at'     => $metadata->publishedAt,
                'duration'         => $metadata->durationSeconds,
                'topic_categories' => $metadata->topicCategories,
                'recipe_generation_status' => RecipeGenerationStatus::FAILED,
                'recipe_generation_error_message' => (new VideoException($error))->getMessage(),
                'generation_retry_count' => $maxRetryCount + 1,
            ]);
        });
    }
}
