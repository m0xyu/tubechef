<?php

namespace App\ValueObjects;

/**
 * Geminiのレスポンス候補（Candidate）をラップするクラス
 */
class GeminiResponseCandidate
{
    /**
     * @param array<string, mixed> $content
     * @param string $finishReason
     * @param GeminiUsageMetadata $usage
     * @param string $modelVersion
     * @param  array<mixed> $safetyRatings
     * @param string|null $finishMessage
     */
    public function __construct(
        public readonly array $content,
        public readonly string $finishReason,
        public readonly GeminiUsageMetadata $usage,
        public readonly string $modelVersion,
        public readonly array $safetyRatings = [],
        public readonly ?string $finishMessage = null,
    ) {}

    /**
     * @param array<string, mixed> $candidate
     * @param GeminiUsageMetadata $usage
     * @param string $modelVersion
     * @return self
     */
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
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->finishReason === 'STOP';
    }

    /**
     * DB保存用（ai_metadataカラム用）の配列に変換
     * @return array<string, mixed>
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

    /**
     * @return array<string, mixed>
     */
    public function getFailureContext(): array
    {
        return array_merge($this->toMetadataArray(), [
            'content_empty' => empty($this->content),
        ]);
    }
}
