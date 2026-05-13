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
        $response = Http::timeout(180)
            ->post("{$this->baseUrl}/generate", [
                'video_id'    => $request->videoId,
                'title'       => $request->title,
                'description' => $request->description,
                'duration_sec' => $request->duration,
            ]);

        if ($response->failed()) {
            Log::error('GoLLMService HTTP error', [
                'status'     => $response->status(),
                'error_code' => $response->json('error_code', 'unknown'),
                'video_id'   => $request->videoId,
            ]);
            throw new RecipeException(RecipeError::GENERATION_FAILED);
        }

        /** @var array<string, mixed> $recipe */
        $recipe = $response->json('data.recipe', []);

        $modelVersion = $response->json('data.metadata.model_version', 'unknown');
        \assert(is_string($modelVersion));

        $finishReason = $response->json('data.metadata.finish_reason', 'FINISH_REASON_UNSPECIFIED');
        \assert(is_string($finishReason));

        /** @var array<string, mixed> $usageData */
        $usageData = $response->json('data.metadata.usage', []);

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
