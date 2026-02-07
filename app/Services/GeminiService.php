<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Http;

class GeminiService
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
     * @return array<string, mixed> 生成されたレシピデータ
     * @throws Exception
     */
    public function generateRecipe(string $title, string $description): array
    {
        $recipeSchema = [
            'type' => 'OBJECT',
            'properties' => [
                'is_recipe' => [
                    'type' => 'BOOLEAN',
                    'description' => '動画の内容が料理レシピであるかどうか',
                ],
                'title' => [
                    'type' => 'STRING',
                    'description' => '料理名。動画タイトルから抽出',
                ],
                'summary' => [
                    'type' => 'STRING',
                    'description' => 'レシピの魅力や要約（100文字程度）',
                ],
                'serving_size' => [
                    'type' => 'STRING',
                    'description' => '分量（例: 2人前）。不明な場合はnull',
                    'nullable' => true,
                ],
                'cooking_time' => [
                    'type' => 'STRING',
                    'description' => '調理時間（例: 15分）。不明な場合はnull',
                    'nullable' => true,
                ],
                'ingredients' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'name' => ['type' => 'STRING', 'description' => '材料名'],
                            'quantity' => ['type' => 'STRING', 'description' => '分量', 'nullable' => true],
                            'group' => [
                                'type' => 'STRING',
                                'description' => '材料のグループ（例: 具材, 調味料, トッピング）。分類不可ならnull',
                                'nullable' => true,
                            ],
                        ],
                        'required' => ['name'],
                    ],
                ],
                'steps' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'step_number' => ['type' => 'INTEGER'],
                            'description' => ['type' => 'STRING', 'description' => '手順の説明'],
                        ],
                        'required' => ['step_number', 'description'],
                    ],
                ],
                'tips' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'description' => ['type' => 'STRING', 'description' => 'コツやポイント'],
                            'related_step_number' => ['type' => 'INTEGER', 'nullable' => true],
                        ],
                        'required' => ['description'],
                    ],
                ],
            ],
            'required' => ['is_recipe', 'title', 'ingredients', 'steps'],
        ];

        $systemInstruction = <<<EOT
            あなたはプロの料理研究家兼データエンジニアです。
            ユーザーから提供される「YouTube動画のタイトル」と「概要欄」を分析し、正確なレシピデータを抽出してください。
            料理動画ではない場合（ゲーム実況やニュースなど）は、is_recipeをfalseにしてください。
            EOT;

        $userPrompt = <<<EOT
            ## 動画タイトル
            {$title}

            ## 概要欄
            {$description}
        EOT;

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}?key={$this->apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $systemInstruction . "\n\n" . $userPrompt]
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
            Log::error('Gemini API Error', ['body' => $response->body()]);
            throw new Exception('Gemini API request failed: ' . $response->status());
        }

        $responseData = $response->json();
        $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

        $result = json_decode($rawText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON Decode Error', ['json' => $rawText]);
            throw new Exception('Failed to decode recipe JSON from AI response.');
        }

        return $result;
    }
}
