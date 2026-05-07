<?php

namespace App\ValueObjects;

/**
 * Geminiのトークン使用量とモダリティ別の詳細を管理するクラス
 */
class GeminiUsageMetadata
{
    /**
     * @param int $promptTokenCount
     * @param int $candidatesTokenCount
     * @param int $totalTokenCount
     * @param int $cachedContentTokenCount
     * @param int $toolUsePromptTokenCount
     * @param int $thoughtsTokenCount
     * @param array<int, array<string, mixed>> $promptDetails
     * @param array<int, array<string, mixed>> $cacheDetails
     * @param array<int, array<string, mixed>> $candidatesDetails
     * @param array<int, array<string, mixed>> $toolUseDetails
     */
    public function __construct(
        public readonly int $promptTokenCount,
        public readonly int $candidatesTokenCount,
        public readonly int $totalTokenCount,
        public readonly int $cachedContentTokenCount = 0,
        public readonly int $toolUsePromptTokenCount = 0,
        public readonly int $thoughtsTokenCount = 0,
        public readonly array $promptDetails = [],
        public readonly array $cacheDetails = [],
        public readonly array $candidatesDetails = [],
        public readonly array $toolUseDetails = [],
    ) {}

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        /** @var array<int, array<string, mixed>> $promptDetails */
        $promptDetails = is_array($data['promptTokensDetails'] ?? null) ? $data['promptTokensDetails'] : [];

        /** @var array<int, array<string, mixed>> $cacheDetails */
        $cacheDetails = is_array($data['cacheTokensDetails'] ?? null) ? $data['cacheTokensDetails'] : [];

        /** @var array<int, array<string, mixed>> $candidatesDetails */
        $candidatesDetails = is_array($data['candidatesTokensDetails'] ?? null) ? $data['candidatesTokensDetails'] : [];

        /** @var array<int, array<string, mixed>> $toolUseDetails */
        $toolUseDetails = is_array($data['toolUsePromptTokensDetails'] ?? null) ? $data['toolUsePromptTokensDetails'] : [];

        return new self(
            totalTokenCount: is_numeric($data['totalTokenCount'] ?? null) ? (int) $data['totalTokenCount'] : 0,
            promptTokenCount: is_numeric($data['promptTokenCount'] ?? null) ? (int) $data['promptTokenCount'] : 0,
            candidatesTokenCount: is_numeric($data['candidatesTokenCount'] ?? null) ? (int) $data['candidatesTokenCount'] : 0,
            cachedContentTokenCount: is_numeric($data['cachedContentTokenCount'] ?? null) ? (int) $data['cachedContentTokenCount'] : 0,
            toolUsePromptTokenCount: is_numeric($data['toolUsePromptTokenCount'] ?? null) ? (int) $data['toolUsePromptTokenCount'] : 0,
            thoughtsTokenCount: is_numeric($data['thoughtsTokenCount'] ?? null) ? (int) $data['thoughtsTokenCount'] : 0,
            promptDetails: $promptDetails,
            cacheDetails: $cacheDetails,
            candidatesDetails: $candidatesDetails,
            toolUseDetails: $toolUseDetails,
        );
    }

    /**
     * 指定されたモダリティ（TEXT, VIDEO等）のトークン数を取得
     * @param array<int, array<string, mixed>> $details
     * @param string $modality
     * @return int
     */
    public function getModalityCount(array $details, string $modality): int
    {
        // $details は [['modality' => 'TEXT', 'tokenCount' => 100], ...] という構造
        $target = collect($details)->firstWhere('modality', strtoupper($modality));

        if (is_array($target) && isset($target['tokenCount']) && is_numeric($target['tokenCount'])) {
            return (int) $target['tokenCount'];
        }

        return 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'prompt_token_count'             => $this->promptTokenCount,
            'cached_content_token_count'     => $this->cachedContentTokenCount,
            'candidates_token_count'         => $this->candidatesTokenCount,
            'tool_use_prompt_token_count'    => $this->toolUsePromptTokenCount,
            'thoughts_token_count'           => $this->thoughtsTokenCount,
            'total_token_count'              => $this->totalTokenCount,
            'prompt_tokens_details'          => $this->promptDetails,
            'cache_tokens_details'           => $this->cacheDetails,
            'candidates_tokens_details'      => $this->candidatesDetails,
            'tool_use_prompt_tokens_details' => $this->toolUseDetails,
        ];
    }
}
