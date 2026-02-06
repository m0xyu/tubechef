<?php

namespace App\Http\Controllers;

use App\Actions\FetchChannelInfoAction;
use App\Actions\FetchYouTubeMetadataAction;
use App\Actions\YouTubeMetadataStoreAction;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VideoController extends Controller
{
    /**
     * 入力されたYouTube動画のプレビュー用メタデータを返す
     *
     * @param Request $request
     * @param FetchYouTubeMetadataAction $fetchYouTubeMetadata
     * @return JsonResponse
     */
    public function preview(Request $request, FetchYouTubeMetadataAction $fetchYouTubeMetadata): JsonResponse
    {
        $request->validate([
            'video_url' => 'required|string',
        ]);

        try {
            $metadata = $fetchYouTubeMetadata->execute($request->input('video_url'));
            return response()->json(['success' => true, 'data' => $metadata]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * YouTube動画のメタデータを保存する
     *
     * @param Request $request
     * @param FetchYouTubeMetadataAction $fetchYouTubeMetadata
     * @param YouTubeMetadataStoreAction $youTubeMetadataStore
     * @param FetchChannelInfoAction $fetchChannelInfo
     * @return JsonResponse
     */
    public function store(
        Request $request,
        FetchYouTubeMetadataAction $fetchYouTubeMetadata,
        FetchChannelInfoAction $fetchChannelInfo,
        YouTubeMetadataStoreAction $youTubeMetadataStore
    ): JsonResponse {
        $request->validate([
            'video_url' => 'required|string',
        ]);

        try {
            $metadata = $fetchYouTubeMetadata->execute($request->input('video_url'), FetchYouTubeMetadataAction::PARTS_FULL);
            $channelInfo = $fetchChannelInfo->execute($metadata['channel_id']);
            $metadata = array_merge($metadata, $channelInfo);
            Log::info('Storing metadata', $metadata);
            $video = $youTubeMetadataStore->execute($metadata);
            return response()->json(['success' => true, 'data' => $video]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
