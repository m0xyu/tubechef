<?php

namespace App\Infrastructure\YouTube;

use App\Enums\Errors\VideoError;
use App\Exceptions\VideoException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;

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
     * @return array{kind: string, id?: string, snippet?: array<string, mixed>, contentDetails?: array<string, mixed>, statistics?: array<string, mixed>, topicDetails?: array<string, mixed>}
     * @throws VideoException
     */
    public function getVideo(string $videoId, array $parts): array
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

        /** @var array{kind: string, id?: string, snippet?: array<string, mixed>, contentDetails?: array<string, mixed>, statistics?: array<string, mixed>, topicDetails?: array<string, mixed>} $result */
        $result = $this->request('videos', $params, $videoId);
        return $result;
    }

    /**
     * YouTube APIを呼び出してチャンネル情報を取得する例
     * @param string $channelId
     * @return array{kind: string, id?: string, snippet?: array<string, mixed>, statistics?: array<string, mixed>}
     * @throws VideoException
     */
    public function getChannel(string $channelId): array
    {
        if (empty($channelId)) {
            throw new \InvalidArgumentException('Channel ID cannot be empty.');
        }
        $params = [
            'part' => 'snippet,statistics',
            'id' => $channelId,
            'key' => $this->apiKey,
        ];

        /** @var array{kind: string, id?: string, snippet?: array<string, mixed>, statistics?: array<string, mixed>} $result */
        $result = $this->request('channels', $params, $channelId);

        return $result;
    }

    /**
     * 共通リクエスト処理
     * @param string $endpoint
     * @param array<string, string> $params
     * @param string|null $contextId
     * @return array<string, mixed>
     * @throws VideoException
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
            throw new VideoException(VideoError::INTERNAL_ERROR, 'Network connection failed.');
        }

        if ($response->failed()) {
            $errorBody = $response->json();
            Log::error('YouTube API Fetch Failed', [
                'status' => $response->status(),
                'response' => $errorBody,
                'context_id' => $contextId
            ]);

            $errorMsg = 'Unknown error';

            if (
                is_array($errorBody) &&
                isset($errorBody['error']) &&
                is_array($errorBody['error']) &&
                isset($errorBody['error']['message']) &&
                is_string($errorBody['error']['message'])
            ) {
                $errorMsg = $errorBody['error']['message'];
            }

            throw new VideoException(VideoError::FETCH_FAILED, $errorMsg);
        }

        $item = $response->json('items.0');

        if (!is_array($item)) {
            Log::warning('YouTube Video Not Found or Private', ['context_id' => $contextId]);
            throw new VideoException(VideoError::FETCH_FAILED, 'No video found or video is private.');
        }

        /** @var array<string, mixed> $item */
        return $item;
    }
}
