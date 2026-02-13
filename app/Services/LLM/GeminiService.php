<?php

namespace App\Services\LLM;

use App\Enums\Errors\RecipeError;
use App\Exceptions\RecipeException;
use App\Services\LLM\LLMServiceInterface;
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
     * @return array<string, mixed> 生成されたレシピデータ
     * @throws Exception
     */
    public function generateRecipe(string $title, string $description, string $videoUrl): array
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
                            'order' => ['type' => 'INTEGER', 'description' => '表示順'],
                        ],
                        'required' => ['name'],
                    ],
                ],
                'dish_name' => [
                    'type' => 'STRING',
                    'description' => '料理の名前だけ。動画タイトルや概要欄から抽出。タイトルと異なる場合もある。シンプルで一般的な名前にして。',
                ],
                'dish_slug' => [
                    'type' => 'STRING',
                    'description' => '料理名のスラッグ（英数字とハイフンのみ）',
                ],
                'steps' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'step_number' => ['type' => 'INTEGER'],
                            'start_time_in_seconds' => [
                                'type' => 'INTEGER',
                                'description' => '手順の開始時間（秒）。不明な場合はnull',
                            ],
                            'end_time_in_seconds' => [
                                'type' => 'INTEGER',
                                'description' => '手順の終了時間（秒）。不明な場合はnull',
                                'nullable' => true,
                            ],
                            'description' => ['type' => 'STRING', 'description' => '手順の説明'],
                        ],
                        'required' => ['step_number', 'description', 'start_time_in_seconds'],
                    ],
                ],
                'tips' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'description' => ['type' => 'STRING', 'description' => '特に大事なコツやポイントを最大5つまで'],
                            'related_step_number' => ['type' => 'INTEGER', 'nullable' => true],
                            'start_time_in_seconds' => [
                                'type' => 'INTEGER',
                                'description' => 'コツが紹介される開始時間（秒）特に重要なコツを最大3つまで。不明な場合はnull',
                                'nullable' => true,
                            ],
                        ],
                        'required' => ['description'],
                    ],
                ],
            ],
            'required' => ['is_recipe', 'title', 'ingredients', 'steps', 'dish_name', 'dish_slug'],
        ];

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

        return $result;
    }
}
