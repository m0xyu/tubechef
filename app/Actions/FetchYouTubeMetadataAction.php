<?php

namespace App\Actions;

use Exception;
use Illuminate\Support\Facades\Http;

class FetchYouTubeMetadataAction
{
    /**
     * 動画のURLまたはIDからYouTubeのメタデータを取得します。
     *
     * @param string $urlOrId YouTubeのURLまたは動画ID
     * @return array 動画のメタデータ（タイトル、概要、サムネイルなど）
     * @throws Exception 取得失敗時
     */
    public function execute(string $urlOrId)
    {
        $videoId = $this->extractVideoId($urlOrId);

        $apiKey = config('services.google.api_key');
        $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
            'part' => 'snippet,contentDetails',
            'id' => $videoId,
            'key' => $apiKey,
        ]);

        if ($response->failed() || empty($response->json('items'))) {
            throw new Exception('Failed to fetch YouTube metadata.');
        }

        $snippet = $response->json('items.0.snippet');
        $details = $response->json('items.0.contentDetails');

        return [
            'video_id' => $videoId,
            'title' => $snippet['title'],
            'description' => $snippet['description'],
            'channel_name' => $snippet['channelTitle'],
            'thumbnail_url' => $snippet['thumbnails']['high']['url'] ?? $snippet['thumbnails']['default']['url'],
            'published_at' => $snippet['publishedAt'],
            'duration_raw' => $details['duration'],
        ];
    }

    /**
     * URLから動画IDを抽出する
     * 対応形式: 
     * - https://www.youtube.com/watch?v=dQw4w9WgXcQ
     * - https://youtu.be/dQw4w9WgXcQ
     * - dQw4w9WgXcQ (ID直接)
     */
    private function extractVideoId(string $urlOrId): string
    {
        if (preg_match('/^[\w-]{11}$/', $urlOrId)) {
            return $urlOrId;
        }

        if (preg_match('/(?:v=|\/)([\w-]{11})(?:&|\?|\/|$)/', $urlOrId, $matches)) {
            return $matches[1];
        }

        throw new Exception('有効なYouTube URLまたは動画IDではありません。');
    }
}
