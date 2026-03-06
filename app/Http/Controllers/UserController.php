<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\VideoPreviewResource;
use App\Models\Video;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * @return AnonymousResourceCollection<VideoPreviewResource>
     */
    public function library(): AnonymousResourceCollection
    {
        /** @var \App\Models\User */
        $user = Auth::user();
        $videos = $user->historyVideos()
            ->select(
                'videos.id',
                'videos.video_id',
                'videos.channel_id',
                'videos.title',
                'videos.thumbnail_url',
                'videos.duration',
                'videos.published_at',
                'videos.recipe_generation_status',
                'videos.recipe_generation_error_message',
                'videos.generation_retry_count'
            )
            ->with(
                'channel:id,name',
                'recipe:id,video_id,slug'
            )->paginate(20);
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

        Gate::authorize('detachHistory', $video);
        $user->historyVideos()->detach($video->id);

        return response()->json([
            'success' => true,
            'message' => '履歴から削除しました。',
        ]);
    }
}
