<?php

namespace App\Actions;

use App\Dtos\YouTubeFullMetadataData;
use App\Enums\Errors\VideoError;
use App\Exceptions\VideoException;
use App\Models\Video;
use App\Models\User;
use App\ValueObjects\YouTubeVideoId;
use App\Enums\RecipeGenerationStatus;
use App\Jobs\GenerateRecipeJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class StoreVideoWorkflowAction
{
    public function __construct(
        private readonly FetchYouTubeMetadataAction $fetchYouTubeMetadata,
        private readonly FetchChannelInfoAction $fetchChannelInfo,
        private readonly YouTubeMetadataStoreAction $youTubeMetadataStore,
    ) {}

    /**
     * @throws VideoException
     */
    public function execute(YouTubeVideoId $videoId, User $user): Video
    {
        // キャッシュロックによる排他制御
        $lockKey = "lock_video_store_{$videoId}";
        $lock = Cache::lock($lockKey, 10);

        if (!$lock->get()) {
            throw new VideoException(VideoError::CONFLICT_REQUEST);
        }

        try {
            $existingVideo = Video::where('video_id', (string)$videoId)->first();

            if ($existingVideo) {
                if ($existingVideo->hasExceededRetryLimit()) {
                    throw new VideoException(VideoError::MAX_RETRY_EXCEEDED);
                }
                if ($existingVideo->isGenerationProcessingOrCompleted()) {
                    return $existingVideo;
                }
            }

            $videoData = $this->fetchYouTubeMetadata->execute($videoId, FetchYouTubeMetadataAction::PARTS_FULL);
            $channelData = $this->fetchChannelInfo->execute($videoData->channelId);

            $youtubeMetadata = new YouTubeFullMetadataData($videoData, $channelData);

            return DB::transaction(function () use ($youtubeMetadata, $user) {
                $video = $this->youTubeMetadataStore->execute($youtubeMetadata);

                $video->update(['recipe_generation_status' => RecipeGenerationStatus::PROCESSING]);
                $user->historyVideos()->syncWithoutDetaching([$video->id]);

                DB::afterCommit(function () use ($video) {
                    GenerateRecipeJob::dispatch($video);
                });

                return $video;
            });
        } finally {
            $lock->release();
        }
    }
}
