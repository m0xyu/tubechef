<?php

namespace App\Services\LLM;

use App\Dtos\LLMResponseData;
use App\Enums\Errors\GeminiError;
use App\Exceptions\GeminiException;
use App\Services\LLM\LLMServiceInterface;
use App\ValueObjects\GeminiResponseCandidate;
use App\ValueObjects\GeminiUsageMetadata;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GeminiService implements LLMServiceInterface
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->baseUrl = config('services.gemini.base_url');
        $this->model = config('services.gemini.flash_model');

        if (empty($this->apiKey)) {
            throw new Exception('Gemini API Key is not set in .env');
        }
    }

    /**
     * スキーマに基づいた構造化データを生成する
     * @param string $prompt
     * @param array<mixed> $schema
     * @param string $systemInstruction
     * @param string $videoUrl
     * @return LLMResponseData
     */
    public function generateStructured(
        string $prompt,
        array $schema,
        string $systemInstruction,
        string $videoUrl
    ): LLMResponseData {
        try {
            $url = $this->buildUrl();
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(180)->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            ['file_data' => ['file_uri' => $videoUrl]]
                        ]
                    ]
                ],
                'system_instruction' => [
                    'parts' => [['text' => $systemInstruction]]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $schema,
                ]
            ]);

            if ($response->failed()) {
                $this->handleError($response);
            }

            return $this->processResponse($response);
        } catch (ConnectionException $e) {
            throw new GeminiException(GeminiError::UNAVAILABLE, '通信エラーが発生しました', $e);
        }
    }

    /**
     * @param \Illuminate\Http\Client\Response $response
     * @return LLMResponseData
     */
    private function processResponse($response): LLMResponseData
    {
        $candidateData = $response->json('candidates.0') ?? [];
        $usageData = $response->json('usageMetadata') ?? [];
        $modelVersion = $response->json('modelVersion') ?? 'unknown';

        $usage = GeminiUsageMetadata::fromArray($usageData);
        $candidate = GeminiResponseCandidate::fromResponse($candidateData, $usage, $modelVersion);
        $usage = $candidate->toMetadataArray();

        // 1. Gemini側での生成成否チェック
        if (!$candidate->isSuccessful()) {
            Log::warning("Gemini generation stopped: {$candidate->finishReason}", $candidate->getFailureContext());
            throw new GeminiException(GeminiError::INTERNAL_ERROR);
        }

        // 2. テキストの抽出
        $text = $candidate->content['parts'][0]['text'] ?? '';

        // 3. JSONパースとエラーハンドリング
        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Gemini JSON Parse Error", [
                'error' => json_last_error_msg(),
                'raw_text' => $text,
                'video_url' => $response->effectiveUri(),
            ]);
            throw new GeminiException(GeminiError::INTERNAL_ERROR, 'AIのレスポンスが解析不可能な形式でした。');
        }

        return new LLMResponseData(
            data: $decoded ?? [],
            model: $modelVersion,
            usage: $usage,
            rawContent: $text
        );
    }

    /**
     * Gemini APIのエンドポイントURLを構築
     * @return string
     */
    private function buildUrl(): string
    {
        return "{$this->baseUrl}{$this->model}:generateContent?key={$this->apiKey}";
    }

    /**
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
