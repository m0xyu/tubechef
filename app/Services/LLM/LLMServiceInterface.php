<?php

namespace App\Services\LLM;

use App\Dtos\GeminiGenerateResultData;
use App\Dtos\GeneratedRecipeData;

interface LLMServiceInterface
{
    /**
     * 動画メタデータからレシピ情報を生成する
     *
     * @param string $title 動画タイトル
     * @param string $description 動画概要欄
     * @param string $videoUrl 動画のURL
     * @return GeneratedRecipeData 生成されたレシピデータ
     */
    public function generateRecipe(string $title, string $description, string $videoUrl): GeneratedRecipeData;

    /**
     * スキーマに基づいた構造化データを生成する
     *
     * @param string $prompt
     * @param string $schema
     * @param string $systemInstruction
     * @param string $videoUrl
     * @return GeminiGenerateResultData
     */
    public function generateStructured(string $prompt, array $schema, string $systemInstruction, string $videoUrl): GeminiGenerateResultData;
}
