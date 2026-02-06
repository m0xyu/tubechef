<?php

namespace App\Http\Controllers;

use App\Actions\FetchYouTubeMetadataAction;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VideoController extends Controller
{
    /**
     * 入力されたYouTube動画のプレビュー用メタデータを返す
     *
     * @param Request $request
     * @param FetchYouTubeMetadataAction $fetchYouTubeMetadata
     * @return void
     */
    public function preview(Request $request, FetchYouTubeMetadataAction $fetchYouTubeMetadata)
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
}
