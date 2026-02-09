<?php

namespace App\Http\Controllers;

use App\Actions\FetchChannelInfoAction;
use App\Actions\FetchYouTubeMetadataAction;
use App\Actions\YouTubeMetadataStoreAction;
use App\Enums\RecipeGenerationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\VideoUrlRequest;
use App\Http\Resources\VideoPreviewResource;
use App\Http\Resources\VideoResource;
use App\Jobs\GenerateRecipeJob;
use App\Models\Video;

class VideoController extends Controller
{
    /**
     * 入力されたYouTube動画のプレビュー用メタデータを返す
     *
     * @param VideoUrlRequest $request
     * @param FetchYouTubeMetadataAction $fetchYouTubeMetadata
     * @return VideoPreviewResource
     */
    public function preview(VideoUrlRequest $request, FetchYouTubeMetadataAction $fetchYouTubeMetadata): VideoPreviewResource
    {
        $metadata = $fetchYouTubeMetadata->execute($request->getVideoUrl(), FetchYouTubeMetadataAction::PARTS_PREVIEW);
        $video = (new Video())->forceFill($metadata);

        return (new VideoPreviewResource($video))
            ->additional(['success' => true]);
    }

    /**
     * YouTube動画のメタデータを保存する
     *
     * @param VideoUrlRequest $request
     * @param FetchYouTubeMetadataAction $fetchYouTubeMetadata
     * @param YouTubeMetadataStoreAction $youTubeMetadataStore
     * @param FetchChannelInfoAction $fetchChannelInfo
     * @return VideoResource
     */
    public function store(
        VideoUrlRequest $request,
        FetchYouTubeMetadataAction $fetchYouTubeMetadata,
        FetchChannelInfoAction $fetchChannelInfo,
        YouTubeMetadataStoreAction $youTubeMetadataStore,
    ): VideoResource {

        $metadata = $fetchYouTubeMetadata->execute(
            $request->getVideoUrl(),
            FetchYouTubeMetadataAction::PARTS_FULL
        );

        $channelInfo = $fetchChannelInfo->execute($metadata['channel_id']);
        $metadata = array_merge($metadata, $channelInfo);
        $video = $youTubeMetadataStore->execute($metadata);

        $video->update(['recipe_generation_status' => RecipeGenerationStatus::PROCESSING]);
        GenerateRecipeJob::dispatch($video);

        $video->load('channel');
        return (new VideoResource($video))
            ->additional(['success' => true]);
    }
}
