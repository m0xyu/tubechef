<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;

class FetchChannelInfoAction
{
    /**
     * YouTubeチャンネル情報を取得する
     * 
     * @param string $channelId
     * @return array<string, mixed>
     */
    public function execute(string $channelId): array
    {
        $apiKey = config('services.google.api_key');

        $response = Http::get('https://www.googleapis.com/youtube/v3/channels', [
            'part' => 'snippet,statistics',
            'id' => $channelId,
            'key' => $apiKey,
        ]);

        if ($response->failed() || empty($response->json('items'))) {
            return [];
        }

        $item = $response->json('items.0');

        return [
            'channel_description' => $item['snippet']['description'] ?? null,
            'channel_custom_url' => $item['snippet']['customUrl'] ?? null,
            'channel_thumbnail_url' => $item['snippet']['thumbnails']['high']['url'] ?? $item['snippet']['thumbnails']['default']['url'],
            'subscriber_count' => $item['statistics']['subscriberCount'] ?? null,
            'channel_view_count' => $item['statistics']['viewCount'] ?? null,
            'channel_video_count' => $item['statistics']['videoCount'] ?? null,
        ];
    }
}
