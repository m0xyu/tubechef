<?php

namespace App\Services\LLM;

use App\Dtos\GeminiGenerateResultData;
use App\Dtos\GeneratedRecipeData;
use App\Enums\Errors\GeminiError;
use App\Enums\Errors\RecipeError;
use App\Exceptions\GeminiException;
use App\Exceptions\RecipeException;
use App\Services\LLM\LLMServiceInterface;
use App\Services\Schemas\RecipeSchema;
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
        $this->model = config('services.gemini.model');

        if (empty($this->apiKey)) {
            throw new Exception('Gemini API Key is not set in .env');
        }
    }

    /**
     * スキーマに基づいた構造化データを生成する
     */
    public function generateStructured(
        string $prompt,
        array $schema,
        string $systemInstruction,
        string $videoUrl
    ): GeminiGenerateResultData {
        try {
            $url = $this->buildUrl();

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(180)->post($url, [
                'contents' => [[
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'file_data' => [
                                'file_uri' => $videoUrl
                            ]
                        ]
                    ]
                ]],
                'system_instruction' => [
                    'parts' => [
                        ['text' => $systemInstruction]
                    ]
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
     * 動画メタデータからレシピ情報を生成する
     *
     * @param string $title 動画タイトル
     * @param string $description 動画概要欄
     * @param string $videoUrl 動画のURL
     * @return GeneratedRecipeData 生成されたレシピデータ
     * @throws Exception
     */
    public function generateRecipe(string $title, string $description, string $videoUrl): GeneratedRecipeData
    {
        $recipeSchema = RecipeSchema::get();
        $url = $this->buildUrl();

        $systemInstruction = <<<EOT
            あなたはプロの料理研究家兼データエンジニアです。
        EOT;

        $userPrompt = <<<EOT
            提供される「YouTube動画（映像・音声）」および「タイトル・概要欄」を総合的に分析し、正確なレシピデータを抽出してください。
            概要欄に分量や手順が記載されていない場合は、動画内の映像や音声解説から情報を補完してください。
            料理動画ではない場合（ゲーム実況やニュースなど）は、is_recipeをfalseにしてください。
    
            ## 動画タイトル
            {$title}

            ## 概要欄
            {$description}
        EOT;

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::timeout(160)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $userPrompt],
                            [
                                'file_data' => [
                                    'file_uri' => $videoUrl
                                ]
                            ]
                        ]
                    ]
                ],
                'system_instruction' => [
                    'parts' => [
                        'text' => $systemInstruction
                    ]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $recipeSchema,
                ]
            ]);

        if ($response->failed()) {
            $status = $response->status();
            $body = $response->body();

            Log::error("Gemini API Error: {$status}", ['body' => $body]);
            if ($status === 429 || $status >= 500) {
                throw new Exception("Gemini API Server Error ({$status}): Temporary failure, retrying.");
            }

            throw new RecipeException(
                RecipeError::GENERATION_FAILED,
                "Gemini API Client Error ({$status}): Check API Key or Request format."
            );
        }

        $responseData = $response->json();
        $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

        $result = json_decode($rawText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON Decode Error', ['json' => $rawText]);
            throw new Exception('Failed to decode recipe JSON from AI response.');
        }

        return GeneratedRecipeData::fromArray($result);
    }

    private function processResponse($response): GeminiGenerateResultData
    {
        $candidateData = $response->json('candidates.0') ?? [];
        $usageData = $response->json('usageMetadata') ?? [];
        $modelVersion = $response->json('modelVersion') ?? 'unknown';
        $usage = GeminiUsageMetadata::fromArray($usageData);
        $candidate = GeminiResponseCandidate::fromResponse($candidateData, $usage, $modelVersion);

        if (!$candidate->isSuccessful()) {
            Log::warning("Gemini generation stopped: {$candidate->finishReason}", $candidate->getFailureContext());
            throw new GeminiException(GeminiError::INTERNAL_ERROR);
        }

        return new GeminiGenerateResultData($candidate);
    }

    /**
     * Gemini APIのエンドポイントURLを構築
     * @return string
     */
    private function buildUrl(): string
    {
        return "{$this->baseUrl}{$this->model}:generateContent?key={$this->apiKey}";
    }

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
