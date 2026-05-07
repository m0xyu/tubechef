<?php

namespace App\Dtos;

/**
 * すべての LLM サービスで共通して使用する戻り値の型
 */
final readonly class LLMResponseData
{
    public function __construct(
        /** @var array<string, mixed> 解析済みの構造化データ（JSONパース済み） */
        public array $data,
        /** 使用したモデル名（'gemini-2.5-flash', 'gpt-4o' など） */
        public string $model,
        /** @var array<string, mixed> model_version / finish_reason / usage を含む共通メタデータ（Go の metadataResponse と同じ形） */
        public array $metadata = [],
        /** デバッグ用などの生のレスポンス（必要に応じて） */
        public ?string $rawContent = null,
    ) {}
}
