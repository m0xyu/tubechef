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
        return new self(
            totalTokenCount: $data['totalTokenCount'] ?? 0,
            promptTokenCount: $data['promptTokenCount'] ?? 0,
            candidatesTokenCount: $data['candidatesTokenCount'] ?? 0,
            cachedContentTokenCount: $data['cachedContentTokenCount'] ?? 0,
            toolUsePromptTokenCount: $data['toolUsePromptTokenCount'] ?? 0,
            thoughtsTokenCount: $data['thoughtsTokenCount'] ?? 0,
            promptDetails: $data['promptTokensDetails'] ?? [],
            cacheDetails: $data['cacheTokensDetails'] ?? [],
            candidatesDetails: $data['candidatesTokensDetails'] ?? [],
            toolUseDetails: $data['toolUsePromptTokensDetails'] ?? [],
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
        return collect($details)->firstWhere('modality', strtoupper($modality))['tokenCount'] ?? 0;
    }

    /**
     * DB保存用またはAPIレスポンス用の配列に変換
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total'    => $this->totalTokenCount,
            'thoughts' => $this->thoughtsTokenCount,
            'breakdown' => [
                'prompt'     => $this->formatPhase($this->promptTokenCount, $this->promptDetails),
                'cache'      => $this->formatPhase($this->cachedContentTokenCount, $this->cacheDetails),
                'candidates' => $this->formatPhase($this->candidatesTokenCount, $this->candidatesDetails),
                'tool_use'   => $this->formatPhase($this->toolUsePromptTokenCount, $this->toolUseDetails),
            ],
        ];
    }

    /**
     * フェーズごとの詳細を整形
     * @param int $total
     * @param array<int, array<string, mixed>> $details
     * @return array<string, int>
     */
    private function formatPhase(int $total, array $details): array
    {
        return [
            'total' => $total,
            'text'  => $this->getModalityCount($details, 'TEXT'),
            'video' => $this->getModalityCount($details, 'VIDEO'),
            'audio' => $this->getModalityCount($details, 'AUDIO'),
            'image' => $this->getModalityCount($details, 'IMAGE'),
            'doc'   => $this->getModalityCount($details, 'DOCUMENT'),
        ];
    }
}
