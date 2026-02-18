<?php

namespace App\Actions;

use App\Dtos\YouTubeVideoData;
use App\Enums\Errors\RecipeError;
use App\Models\Video;
use App\Enums\RecipeGenerationStatus;
use App\Exceptions\RecipeException;
use App\Models\Channel;
use App\ValueObjects\YouTubeVideoId;
use Illuminate\Support\Facades\DB;

class GetVideoPreviewAction
{
    public function __construct(
        private FetchYouTubeMetadataAction $fetchYouTubeMetadata
    ) {}

    /**
     * 動画のプレビュー用インスタンスを取得する
     * 料理以外はここで「最終失敗」として保存される
     * @param YouTubeVideoId $videoId
     * @return Video 
     */
    public function execute(YouTubeVideoId $videoId): Video
    {
        $video = Video::where('video_id', (string)$videoId)->with(['recipe'])->first();
        if ($video) {
            return $video;
        }

        $metadata = $this->fetchYouTubeMetadata->execute($videoId, FetchYouTubeMetadataAction::PARTS_PREVIEW);

        if (!in_array('Food', $metadata->topicCategories ?? [])) {
            return $this->createInvalidVideo($metadata);
        }

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
     * 料理動画ではないものを「生成不可」として永続化する
     * @param YouTubeVideoData $metadata 
     */
    private function createInvalidVideo(YouTubeVideoData $metadata): Video
    {
        return DB::transaction(function () use ($metadata) {
            $channel = Channel::firstOrCreate(
                ['channel_id' => $metadata->channelId],
                ['name' => $metadata->channelName]
            );

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
                'recipe_generation_error_message' => (new RecipeException(RecipeError::NOT_A_RECIPE))->getMessage(),
                'generation_retry_count' => config('services.gemini.retry_count', 2) + 1,
            ]);
        });
    }
}
