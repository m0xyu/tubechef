<?php

namespace App\Actions;

use App\Dtos\YouTubeChannelData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

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
        $item = $this->fetchChannelInfo($channelId);
        if (!$item) {
            // 例外を投げる設計も検討可能
            // throw new \RuntimeException("Failed to fetch channel info for ID: {$channelId}");
            return YouTubeChannelData::empty();
        }
        return $this->toDto($item);
    }

    /**
     * APIリクエストとレスポンスバリデーション
     * @param string $channelId
     * @return array<string, mixed>|null
     */
    protected function fetchChannelInfo(string $channelId): ?array
    {
        $baseUrlRaw = config('services.youtube.base_url');
        $baseUrl = is_string($baseUrlRaw) ? $baseUrlRaw : '';
        $apiKeyRaw = config('services.google.api_key');
        $apiKey = is_string($apiKeyRaw) ? $apiKeyRaw : '';

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
            return null;
        }

        $item = $response->json('items.0');
        if (!is_array($item)) {
            return null;
        }

        // array<string, mixed> になるようkeyをstringに限定
        $result = [];
        foreach ($item as $k => $v) {
            if (is_string($k)) {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    /**
     * 配列からDTOへ変換
     * @param array<string, mixed> $item
     * @return YouTubeChannelData
     */
    protected function toDto(array $item): YouTubeChannelData
    {
        $snippet = is_array($item['snippet'] ?? null) ? $item['snippet'] : [];
        $statistics = is_array($item['statistics'] ?? null) ? $item['statistics'] : [];

        $desc = Arr::get($snippet, 'description');
        $customUrl = Arr::get($snippet, 'customUrl');
        $thumbHigh = Arr::get($snippet, 'thumbnails.high.url');
        $thumbDefault = Arr::get($snippet, 'thumbnails.default.url');
        $subscriberCount = Arr::get($statistics, 'subscriberCount');
        $viewCount = Arr::get($statistics, 'viewCount');
        $videoCount = Arr::get($statistics, 'videoCount');

        $resultArray = [
            'channel_description' => is_string($desc) ? $desc : null,
            'channel_custom_url' => is_string($customUrl) ? $customUrl : null,
            'channel_thumbnail_url' => is_string($thumbHigh) ? $thumbHigh : (is_string($thumbDefault) ? $thumbDefault : null),
            'subscriber_count' => is_numeric($subscriberCount) ? (int)$subscriberCount : null,
            'channel_view_count' => is_numeric($viewCount) ? (int)$viewCount : null,
            'channel_video_count' => is_numeric($videoCount) ? (int)$videoCount : null,
        ];

        return YouTubeChannelData::fromArray($resultArray);
    }
}
