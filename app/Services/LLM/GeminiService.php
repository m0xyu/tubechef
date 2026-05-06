<?php

namespace App\Services\LLM;

use App\Dtos\LLMRequestData;
use App\Dtos\LLMResponseData;
use App\Enums\Errors\GeminiError;
use App\Exceptions\GeminiException;
use App\Infrastructure\Gemini\GeminiApiClient;
use App\Services\LLM\LLMServiceInterface;
use App\Services\LLM\Prompts\RecipePrompt;
use App\Services\LLM\Schemas\RecipeSchema;
use App\ValueObjects\GeminiResponseCandidate;
use App\ValueObjects\GeminiUsageMetadata;
use Illuminate\Support\Facades\Log;

class GeminiService implements LLMServiceInterface
{
    public function __construct(
        private readonly GeminiApiClient $apiClient
    ) {}

    public function generate(LLMRequestData $request): LLMResponseData
    {
        $prompt = RecipePrompt::buildFromRequest($request);
        $schema = RecipeSchema::get();
        $systemInstruction = RecipePrompt::systemInstruction();

        $payload = $this->buildPayload($prompt, $schema, $systemInstruction, $request->videoUrl);
        $responseArray = $this->apiClient->post($payload);

        return $this->processResponse($responseArray, $request->videoUrl);
    }

    /**
     * @param string $prompt
     * @param array<mixed> $schema
     * @param string $systemInstruction
     * @param string $videoUrl
     * @return array<string, mixed>
     */
    private function buildPayload(string $prompt, array $schema, string $systemInstruction, string $videoUrl): array
    {
        return [
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
        ];
    }

    /**
     * @param array<string, mixed> $responseData
     * @param string $videoUrl
     * @return LLMResponseData
     */
    private function processResponse(array $responseData, string $videoUrl): LLMResponseData
    {
        /** @var array<mixed> $candidatesRaw */
        $candidatesRaw = is_array($responseData['candidates'] ?? null) ? $responseData['candidates'] : [];

        /** @var array<string, mixed> $candidateData */
        $candidateData = (isset($candidatesRaw[0]) && is_array($candidatesRaw[0])) ? $candidatesRaw[0] : [];

        /** @var array<string, mixed> $usageData */
        $usageData = is_array($responseData['usageMetadata'] ?? null) ? $responseData['usageMetadata'] : [];

        $modelVersionRaw = $responseData['modelVersion'] ?? 'unknown';
        $modelVersion = is_string($modelVersionRaw) ? $modelVersionRaw : 'unknown';

        $usage = GeminiUsageMetadata::fromArray($usageData);
        $candidate = GeminiResponseCandidate::fromResponse($candidateData, $usage, $modelVersion);

        $usageArray = $candidate->toMetadataArray();

        if (!$candidate->isSuccessful()) {
            Log::warning("Gemini generation stopped: {$candidate->finishReason}", $candidate->getFailureContext());
            throw new GeminiException(GeminiError::INTERNAL_ERROR);
        }

        /** @var array<string, mixed> $content */
        $content = $candidate->content;

        /** @var array<mixed> $parts */
        $parts = is_array($content['parts'] ?? null) ? $content['parts'] : [];

        $firstPart = $parts[0] ?? [];
        $textRaw = is_array($firstPart) ? ($firstPart['text'] ?? '') : '';
        $text = is_string($textRaw) ? $textRaw : '';

        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Gemini JSON Parse Error", [
                'error' => json_last_error_msg(),
                'raw_text' => $text,
                'video_url' => $videoUrl,
            ]);
            throw new GeminiException(GeminiError::INTERNAL_ERROR, 'AIのレスポンスが解析不可能な形式でした。');
        }

        /** @var array<string, mixed> $data */
        $data = is_array($decoded) ? $decoded : [];

        return new LLMResponseData(
            data: $data,
            model: $modelVersion,
            usage: $usageArray,
            rawContent: $text
        );
    }
}
