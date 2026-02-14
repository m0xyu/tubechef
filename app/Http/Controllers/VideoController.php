<?php

namespace App\Http\Controllers;

use App\Actions\FetchChannelInfoAction;
use App\Actions\FetchYouTubeMetadataAction;
use App\Actions\YouTubeMetadataStoreAction;
use App\Enums\Errors\VideoError;
use App\Enums\RecipeGenerationStatus;
use App\Exceptions\VideoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\VideoUrlRequest;
use App\Http\Resources\VideoPreviewResource;
use App\Jobs\GenerateRecipeJob;
use App\Models\Video;
use Illuminate\Support\Facades\DB;
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
        $videoId = $fetchYouTubeMetadata->extractVideoId($request->getVideoUrl());

        $video = Video::where('video_id', $videoId)
            ->with(['recipe'])
            ->first();

        if (!$video) {
            $metadata = $fetchYouTubeMetadata->execute($request->getVideoUrl(), FetchYouTubeMetadataAction::PARTS_PREVIEW);
            $video = (new Video())->forceFill($metadata);
        }

        return (new VideoPreviewResource($video))
            ->additional([
                'success' => true,
            ]);
    }

    /**
     * YouTube動画のメタデータを保存する
     *
     * @param VideoUrlRequest $request
     * @param FetchYouTubeMetadataAction $fetchYouTubeMetadata
     * @param YouTubeMetadataStoreAction $youTubeMetadataStore
     * @param FetchChannelInfoAction $fetchChannelInfo
     * @return VideoPreviewResource
     * @throws VideoException
     */
    public function store(
        VideoUrlRequest $request,
        FetchYouTubeMetadataAction $fetchYouTubeMetadata,
        FetchChannelInfoAction $fetchChannelInfo,
        YouTubeMetadataStoreAction $youTubeMetadataStore,
    ): VideoPreviewResource {
        $videoId = $fetchYouTubeMetadata->extractVideoId($request->getVideoUrl());
        $existingVideo = Video::where('video_id', $videoId)->first();

        if ($existingVideo) {
            // 1. もうリトライの上限を超えてしまっている場合 → エラー
            if ($existingVideo->hasExceededRetryLimit()) {
                throw new VideoException(VideoError::MAX_RETRY_EXCEEDED);
            }

            // 2. すでに成功しているか、処理中の場合 → そのまま返す
            if ($existingVideo->isGenerationProcessingOrCompleted()) {
                return new VideoPreviewResource($existingVideo->load('channel', 'recipe'));
            }
        }

        $metadata = $fetchYouTubeMetadata->execute(
            $request->getVideoUrl(),
            FetchYouTubeMetadataAction::PARTS_FULL
        );

        $channelInfo = $fetchChannelInfo->execute($metadata['channel_id']);
        $metadata = array_merge($metadata, $channelInfo);

        $video = DB::transaction(function () use ($youTubeMetadataStore, $metadata, $request) {
            $video = $youTubeMetadataStore->execute($metadata);

            $video->update(['recipe_generation_status' => RecipeGenerationStatus::PROCESSING]);
            $request->user()->historyVideos()->syncWithoutDetaching([$video->id]);

            GenerateRecipeJob::dispatch($video);

            return $video;
        });

        return (new VideoPreviewResource($video->load('channel', 'recipe')))->additional(['success' => true]);
    }

    /**
     * 動画のレシピ生成ステータスを確認する（ポーリング用）
     *
     * @param string $videoId (YouTubeのID)
     * @return JsonResponse
     */
    public function checkStatus(string $videoId): JsonResponse
    {
        $video = Video::where('video_id', $videoId)
            ->select(['id', 'video_id', 'recipe_generation_status', 'recipe_generation_error_message'])
            ->firstOrFail();

        if ($video->recipe_generation_status === RecipeGenerationStatus::COMPLETED) {
            // N+1対策でロード
            $video->load('recipe');

            return response()->json([
                'status' => 'completed',
                'action_type' => 'view_recipe',
                'recipe_slug' => $video->recipe ? $video->recipe->slug : null,
            ]);
        }

        // 失敗時
        if ($video->recipe_generation_status === RecipeGenerationStatus::FAILED) {
            return response()->json([
                'status' => 'failed',
                'action_type' => 'generate',
                'error_message' => $video->recipe_generation_error_message,
            ]);
        }

        // 処理中 (processing)
        return response()->json([
            'status' => 'processing',
            'action_type' => 'processing',
        ]);
    }
}
