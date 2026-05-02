<?php

use App\Enums\Errors\VideoError;
use App\Exceptions\VideoException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeApiClient
{
    private int $retryCount;
    private int $retryDelayMs;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        int $retryCount = 3,
        int $retryDelayMs = 1000
    ) {
        $this->retryCount = $retryCount;
        $this->retryDelayMs = $retryDelayMs;
    }

    /**
     * YouTube APIを呼び出して動画情報を取得する例
     * @param string $videoId
     * @param array<string> $parts
     * @return array{kind: string, etag?: string, id?: string, snippet?: array, contentDetails?: array, statistics?: array, topicDetails?: array}
     * @throws VideoException
     */
    public function getVideos(string $videoId, array $parts): array
    {
        if (empty($videoId)) {
            throw new \InvalidArgumentException('Video ID cannot be empty.');
        }

        if (empty($parts)) {
            throw new \InvalidArgumentException('Parts parameter cannot be empty.');
        }

        $params = [
            'part' => implode(',', $parts),
            'id' => $videoId,
            'key' => $this->apiKey,
        ];
        return $this->request('videos', $params, $videoId);
    }

    /**
     * YouTube APIを呼び出してチャンネル情報を取得する例
     * @param string $channelId
     * @return array{kind: string, etag?: string, id?: string, snippet?: array, statistics?: array}
     * @throws VideoException
     */
    public function getChannels(string $channelId): array
    {
        if (empty($channelId)) {
            throw new \InvalidArgumentException('Channel ID cannot be empty.');
        }
        $params = [
            'part' => 'snippet,statistics',
            'id' => $channelId,
            'key' => $this->apiKey,
        ];
        return $this->request('channels', $params, $channelId);
    }

    /**
     * 共通リクエスト処理
     * @template T of array
     * @param string $endpoint
     * @param array<string, string> $params
     * @param string|null $contextId
     * @return T
     * @throws VideoException
     */
    private function request(string $endpoint, array $params, ?string $contextId = null): array
    {
        $response = Http::retry(
            $this->retryCount,
            $this->retryDelayMs,
            function (\Exception $e) use ($contextId) {
                Log::warning('YouTube API Request Failed, retrying...', [
                    'context_id' => $contextId,
                    'error' => $e->getMessage(),
                ]);
                return true;
            }
        )->get("{$this->baseUrl}/{$endpoint}", $params);

        if ($response->failed()) {
            $errorBody = $response->json();
            Log::error('YouTube API Fetch Failed', [
                'status' => $response->status(),
                'response' => $errorBody,
                'context_id' => $contextId
            ]);
            $errorMsg = is_array($errorBody) && isset($errorBody['error']['message'])
                ? $errorBody['error']['message']
                : 'Unknown error';
            throw new VideoException(VideoError::FETCH_FAILED, $errorMsg);
        }

        $item = $response->json('items.0');

        if (!is_array($item)) {
            Log::warning('YouTube Video Not Found or Private', ['context_id' => $contextId]);
            throw new VideoException(VideoError::FETCH_FAILED, 'No video found or video is private.');
        }

        return $item;
    }
}
