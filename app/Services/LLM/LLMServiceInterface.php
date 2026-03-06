<?php

namespace App\Services\LLM;

use App\Dtos\GeminiGenerateResultData;

interface LLMServiceInterface
{
    /**
     * スキーマに基づいた構造化データを生成する
     *
     * @param string $prompt
     * @param array<string, mixed> $schema
     * @param string $systemInstruction
     * @param string $videoUrl
     * @return GeminiGenerateResultData
     */
    public function generateStructured(string $prompt, array $schema, string $systemInstruction, string $videoUrl): GeminiGenerateResultData;
}
