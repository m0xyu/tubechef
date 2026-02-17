<?php

namespace App\Actions;

use App\Dtos\YouTubeChannelData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchChannelInfoAction
{
    /**
     * YouTubeチャンネル情報を取得する
     * 
     * @param string $channelId
     * @return YouTubeChannelData
     */
    public function execute(string $channelId): YouTubeChannelData
    {
        $baseUrl = config('services.youtube.base_url');
        $apiKey = config('services.google.api_key');

        $response = Http::get("{$baseUrl}/channels", [
            'part' => 'snippet,statistics',
            'id' => $channelId,
            'key' => $apiKey,
        ]);

        if ($response->failed() || empty($response->json('items'))) {
            Log::warning("Failed to fetch channel info for ID: {$channelId}", [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return YouTubeChannelData::empty();
        }

        $item = $response->json('items.0');
        $snippet = $item['snippet'] ?? [];
        $statistics = $item['statistics'] ?? [];

        $resultArray = [
            'channel_description' => $snippet['description'] ?? null,
            'channel_custom_url' => $snippet['customUrl'] ?? null,
            'channel_thumbnail_url' => $snippet['thumbnails']['high']['url'] ?? $snippet['thumbnails']['default']['url'] ?? null,
            'subscriber_count' => $statistics['subscriberCount'] ?? null,
            'channel_view_count' => $statistics['viewCount'] ?? null,
            'channel_video_count' => $statistics['videoCount'] ?? null,
        ];

        return YouTubeChannelData::fromArray($resultArray);
    }
}
