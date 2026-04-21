<?php

namespace App\Services\LLM;

use App\Dtos\LLMResponseData;

interface LLMServiceInterface
{
    /**
     * スキーマに基づいた構造化データを生成する
     *
     * @param string $prompt
     * @param array<string, mixed> $schema
     * @param string $systemInstruction
     * @param string $videoUrl
     * @return LLMResponseData
     */
    public function generateStructured(string $prompt, array $schema, string $systemInstruction, string $videoUrl): LLMResponseData;
}
