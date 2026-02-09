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
use Symfony\Component\HttpFoundation\JsonResponse;

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

    /**
     * 動画のレシピ生成ステータスを確認する（ポーリング用）
     *
     * @param string $videoId (YouTubeのID または DBのID)
     * @return JsonResponse
     */
    public function checkStatus(string $videoId): JsonResponse
    {
        $video = Video::where('video_id', $videoId)
            ->select(['id', 'video_id', 'recipe_generation_status', 'recipe_generation_error_message'])
            ->firstOrFail();

        return response()->json([
            'status' => $video->recipe_generation_status,
            'error_message' => $video->recipe_generation_error_message,
        ]);
    }
}
