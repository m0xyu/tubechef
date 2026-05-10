<?php

namespace App\Services\LLM;

use App\Dtos\LLMRequestData;
use App\Dtos\LLMResponseData;
use App\Enums\Errors\RecipeError;
use App\Exceptions\RecipeException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoLLMService implements LLMServiceInterface
{
    public function __construct(
        private readonly string $baseUrl,
    ) {}

    public function generate(LLMRequestData $request): LLMResponseData
    {
        $response = Http::timeout(360)
            ->post("{$this->baseUrl}/generate", [
                'video_id'    => $request->videoId,
                'title'       => $request->title,
                'description' => $request->description,
                'duration_sec' => $request->duration,
            ]);

        if ($response->failed()) {
            $body = $response->json();
            $errorCode = is_array($body) ? ($body['error_code'] ?? 'unknown') : 'unknown';
            Log::error('GoLLMService HTTP error', [
                'status'     => $response->status(),
                'error_code' => $errorCode,
                'video_id'   => $request->videoId,
            ]);
            throw new RecipeException(RecipeError::GENERATION_FAILED);
        }

        /** @var array<string, mixed> $body */
        $body = $response->json();

        /** @var array<string, mixed> $data */
        $data = is_array($body['data'] ?? null) ? $body['data'] : [];

        /** @var array<string, mixed> $recipe */
        $recipe = is_array($data['recipe'] ?? null) ? $data['recipe'] : [];

        /** @var array<string, mixed> $metadata */
        $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];

        $modelVersion = is_string($metadata['model_version'] ?? null) ? $metadata['model_version'] : 'unknown';
        $finishReason = is_string($metadata['finish_reason'] ?? null) ? $metadata['finish_reason'] : 'FINISH_REASON_UNSPECIFIED';

        /** @var array<string, mixed> $usageData */
        $usageData = is_array($metadata['usage'] ?? null) ? $metadata['usage'] : [];

        return new LLMResponseData(
            data: $recipe,
            model: $modelVersion,
            metadata: [
                'model_version' => $modelVersion,
                'finish_reason' => $finishReason,
                'usage'         => $usageData,
            ],
        );
    }
}
