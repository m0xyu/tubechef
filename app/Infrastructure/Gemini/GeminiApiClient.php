<?php

namespace App\Infrastructure\Gemini;

use App\Enums\Errors\GeminiError;
use App\Exceptions\GeminiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiApiClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $model,
        private readonly int $timeout = 180
    ) {}

    /**
     * Gemini APIへPOSTリクエストを送信する
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     * @throws GeminiException
     */
    public function post(array $payload): array
    {
        $url = "{$this->baseUrl}{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::timeout($this->timeout)->post($url, $payload);

            if ($response->failed()) {
                $this->handleError($response);
            }

            /** @var array<string, mixed> $json */
            $json = $response->json();
            return $json;
        } catch (ConnectionException $e) {
            Log::error('Gemini API Connection Error', ['error' => $e->getMessage()]);
            throw new GeminiException(GeminiError::UNAVAILABLE, 'Network connection failed.', $e);
        }
    }

    /**
     * HTTPエラーをキャッチし、ドメイン例外に変換する
     *
     * @param \Illuminate\Http\Client\Response $response
     * @throws GeminiException
     */
    private function handleError($response): void
    {
        $status = $response->status();
        $error = match ($status) {
            400 => GeminiError::INVALID_ARGUMENT,
            403 => GeminiError::PERMISSION_DENIED,
            404 => GeminiError::NOT_FOUND,
            429 => GeminiError::RESOURCE_EXHAUSTED,
            503 => GeminiError::UNAVAILABLE,
            504 => GeminiError::DEADLINE_EXCEEDED,
            default => GeminiError::INTERNAL_ERROR,
        };

        Log::error("Gemini API Error: {$error->value}", [
            'status' => $status,
            'message' => $error->message(),
            'body' => $response->body()
        ]);

        throw new GeminiException($error);
    }
}
