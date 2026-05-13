<?php

declare(strict_types=1);

namespace App\Infrastructure\YouTube;

use App\Exceptions\YouTubeApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;

class YouTubeApiClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $retryCount = 3,
        private readonly int $retryDelayMs = 1000
    ) {}

    /**
     * YouTube APIを呼び出して動画情報を取得する例
     * @param string $videoId
     * @param array<string> $parts
     * @return array<string, mixed>
     * @throws YouTubeApiException
     */
    public function getVideo(string $videoId, array $parts): array
    {
        if ($videoId === '') {
            throw new \InvalidArgumentException('Video ID cannot be empty.');
        }

        if ($parts === []) {
            throw new \InvalidArgumentException('Parts parameter cannot be empty.');
        }

        return $this->request('videos', [
            'part' => implode(',', $parts),
            'id' => $videoId,
            'key' => $this->apiKey,
        ], $videoId);
    }

    /**
     * @param string $channelId
     * @return array<string, mixed>
     * @throws YouTubeApiException
     */
    public function getChannel(string $channelId): array
    {
        if ($channelId === '') {
            throw new \InvalidArgumentException('Channel ID cannot be empty.');
        }

        return $this->request('channels', [
            'part' => 'snippet,statistics',
            'id' => $channelId,
            'key' => $this->apiKey,
        ], $channelId);
    }

    /**
     * 共通リクエスト処理
     * @param string $endpoint
     * @param array<string, string> $params
     * @param string|null $contextId
     * @return array<string, mixed>
     * @throws YouTubeApiException
     */
    private function request(string $endpoint, array $params, ?string $contextId = null): array
    {
        try {
            $response = Http::retry(
                $this->retryCount,
                $this->retryDelayMs,
                function (\Throwable $e) use ($contextId) {
                    Log::warning('YouTube API Request Failed, retrying...', [
                        'context_id' => $contextId,
                        'error' => $e->getMessage(),
                    ]);
                    return true;
                }
            )->get("{$this->baseUrl}/{$endpoint}", $params);
        } catch (RequestException $e) {
            $response = $e->response;
        } catch (ConnectionException $e) {
            Log::error('YouTube API Connection Error', [
                'context_id' => $contextId,
                'error' => $e->getMessage()
            ]);
            throw new YouTubeApiException('Network connection failed.', 0, $e);
        }

        if ($response->failed()) {
            $errorMsgRaw = $response->json('error.message');
            $errorMsg = is_string($errorMsgRaw) ? $errorMsgRaw : 'Unknown error';

            Log::error('YouTube API Fetch Failed', [
                'status' => $response->status(),
                'response' => $response->json(),
                'context_id' => $contextId
            ]);

            throw new YouTubeApiException("YouTube API Error: {$errorMsg}");
        }

        $item = $response->json('items.0');

        if (!is_array($item)) {
            Log::warning('YouTube Video Not Found or Private', ['context_id' => $contextId]);
            throw new YouTubeApiException('No data found or it is private.');
        }

        /** @var array<string, mixed> $item */
        return $item;
    }
}
