<?php

namespace App\Services\LLM;

use App\Dtos\GeneratedRecipeData;
use App\Enums\Errors\RecipeError;
use App\Exceptions\RecipeException;
use App\Services\LLM\LLMServiceInterface;
use App\Services\Schemas\RecipeSchema;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Http;

class GeminiService implements LLMServiceInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');

        if (empty($this->apiKey)) {
            throw new Exception('Gemini API Key is not set in .env');
        }
    }

    /**
     * 動画メタデータからレシピ情報を生成する
     *
     * @param string $title 動画タイトル
     * @param string $description 動画概要欄
     * @param string $videoUrl 動画のURL
     * @return GeneratedRecipeData 生成されたレシピデータ
     * @throws Exception
     */
    public function generateRecipe(string $title, string $description, string $videoUrl): GeneratedRecipeData
    {
        $recipeSchema = RecipeSchema::get();

        $systemInstruction = <<<EOT
            あなたはプロの料理研究家兼データエンジニアです。
            提供される「YouTube動画（映像・音声）」および「タイトル・概要欄」を総合的に分析し、正確なレシピデータを抽出してください。
            概要欄に分量や手順が記載されていない場合は、動画内の映像や音声解説から情報を補完してください。
            料理動画ではない場合（ゲーム実況やニュースなど）は、is_recipeをfalseにしてください。
        EOT;

        $userPrompt = <<<EOT
            ## 動画タイトル
            {$title}

            ## 概要欄
            {$description}
        EOT;

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::timeout(120)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $systemInstruction . "\n\n" . $userPrompt],
                            [
                                'file_data' => [
                                    'file_uri' => $videoUrl
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $recipeSchema,
                ]
            ]);

        if ($response->failed()) {
            $status = $response->status();
            $body = $response->body();

            Log::error("Gemini API Error: {$status}", ['body' => $body]);
            if ($status === 429 || $status >= 500) {
                throw new Exception("Gemini API Server Error ({$status}): Temporary failure, retrying.");
            }

            throw new RecipeException(
                RecipeError::GENERATION_FAILED,
                "Gemini API Client Error ({$status}): Check API Key or Request format."
            );
        }

        $responseData = $response->json();
        $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

        $result = json_decode($rawText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON Decode Error', ['json' => $rawText]);
            throw new Exception('Failed to decode recipe JSON from AI response.');
        }

        return GeneratedRecipeData::fromArray($result);
    }
}
