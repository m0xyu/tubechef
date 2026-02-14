<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\VideoPreviewResource;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * @return AnonymousResourceCollection<VideoPreviewResource>
     */
    public function library(): AnonymousResourceCollection
    {
        /** @var \App\Models\User */
        $user = Auth::user();
        Log::info($user);
        $videos = $user->historyVideos()->with('channel', 'recipe')->get();
        return VideoPreviewResource::collection($videos);
    }

    /**
     * レシピ生成したビデオの履歴を削除する
     * @param string $videoId
     * @return JsonResponse
     */
    public function library_delete(string $videoId): JsonResponse
    {
        /** @var \App\Models\User */
        $user = Auth::user();

        $video = Video::where('video_id', $videoId)->firstOrFail();
        $user->historyVideos()->detach($video->id);

        return response()->json([
            'success' => true,
            'message' => '履歴から削除しました。',
        ]);
    }
}
