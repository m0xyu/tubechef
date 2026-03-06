<?php

namespace App\ValueObjects;

/**
 * Geminiのレスポンス候補（Candidate）をラップするクラス
 */
class GeminiResponseCandidate
{
    public function __construct(
        public readonly array $content,
        public readonly string $finishReason,
        public readonly GeminiUsageMetadata $usage,
        public readonly string $modelVersion,
        public readonly array $safetyRatings = [],
        public readonly ?string $finishMessage = null,
    ) {}

    public static function fromResponse(array $candidate, GeminiUsageMetadata $usage, string $modelVersion): self
    {
        return new self(
            content: $candidate['content'] ?? [],
            finishReason: $candidate['finishReason'] ?? 'OTHER',
            usage: $usage,
            modelVersion: $modelVersion,
            safetyRatings: $candidate['safetyRatings'] ?? [],
            finishMessage: $candidate['finishMessage'] ?? null,
        );
    }

    /**
     * 正常に終了したか判定
     */
    public function isSuccessful(): bool
    {
        return $this->finishReason === 'STOP';
    }

    /**
     * DB保存用（ai_metadataカラム用）の配列に変換
     */
    public function toMetadataArray(): array
    {
        return [
            'model_version'  => $this->modelVersion,
            'finish_reason'  => $this->finishReason,
            'tokens'         => $this->usage->toArray(),
            'safety_ratings' => $this->safetyRatings,
            'evaluated_at'   => now()->toDateTimeString(),
            'finish_message' => $this->finishMessage,
        ];
    }

    public function getFailureContext(): array
    {
        return array_merge($this->toMetadataArray(), [
            'content_empty' => empty($this->content),
        ]);
    }
}
