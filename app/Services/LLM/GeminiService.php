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
use Illuminate\Support\Arr;
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
        /** @var array<string, mixed> $candidateData */
        $candidateData = Arr::get($responseData, 'candidates.0', []);

        /** @var array<string, mixed> $usageData */
        $usageData = Arr::get($responseData, 'usageMetadata', []);

        $modelVersion = Arr::get($responseData, 'modelVersion', 'unknown');
        \assert(is_string($modelVersion));

        $usage = GeminiUsageMetadata::fromArray($usageData);
        $candidate = GeminiResponseCandidate::fromResponse($candidateData, $usage, $modelVersion);

        $metadata = $candidate->toMetadataArray();

        if (!$candidate->isSuccessful()) {
            Log::warning("Gemini generation stopped: {$candidate->finishReason}", $candidate->getFailureContext());
            throw new GeminiException(GeminiError::INTERNAL_ERROR);
        }

        $text = Arr::get($candidate->content, 'parts.0.text', '');
        \assert(is_string($text));

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
            metadata: $metadata,
            rawContent: $text
        );
    }
}
