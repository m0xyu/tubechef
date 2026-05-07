<?php

namespace App\Services\LLM\Prompts;

use App\Dtos\LLMRequestData;
use App\Models\Video;

class RecipePrompt
{
    /**
     * システムインストラクション（AIの役割定義）
     */
    public static function systemInstruction(): string
    {
        return 'あなたはプロの料理研究家兼データエンジニアです。';
    }

    /**
     * 動画データからユーザープロンプトを構築する
     */
    public static function build(Video $video): string
    {
        return self::buildPrompt($video->title, $video->description ?? '');
    }

    /**
     * LLMRequestDataからユーザープロンプトを構築する
     */
    public static function buildFromRequest(LLMRequestData $request): string
    {
        return self::buildPrompt($request->title, $request->description);
    }

    private static function buildPrompt(string $title, string $description): string
    {
        return <<<EOT
            提供される「YouTube動画（映像・音声）」および「タイトル・概要欄」を総合的に分析し、正確なレシピデータを抽出してください。
            概要欄に分量や手順が記載されていない場合は、動画内の映像や音声解説から情報を補完してください。
            料理動画ではない場合（ゲーム実況やニュースなど）は、is_recipeをfalseにしてください。
    
            ## 動画タイトル
            {$title}

            ## 概要欄
            {$description}
        EOT;
    }
}
