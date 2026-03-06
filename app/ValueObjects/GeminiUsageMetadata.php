<?php

namespace App\ValueObjects;

class GeminiUsageMetadata
{
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

    public function getModalityCount(array $details, string $modality): int
    {
        return collect($details)->firstWhere('modality', strtoupper($modality))['tokenCount'] ?? 0;
    }

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
