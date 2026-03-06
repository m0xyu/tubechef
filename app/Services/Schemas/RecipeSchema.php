<?php

namespace App\Services\Schemas;

final class RecipeSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'is_recipe' => [
                    'type' => 'boolean',
                    'description' => '動画の内容が料理レシピであるかどうか',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => '料理名。動画タイトルから抽出',
                ],
                'summary' => [
                    'type' => 'string',
                    'description' => 'レシピの魅力や要約（100文字程度）',
                ],
                'serving_size' => [
                    'type' => 'string', // 配列形式 ["string", "null"] を回避
                    'nullable' => true,  // nullable キーで対応
                    'description' => '分量（例: 2人前）。不明な場合はnull',
                ],
                'cooking_time' => [
                    'type' => 'string',
                    'nullable' => true,
                    'description' => '調理時間（例: 15分）。不明な場合はnull',
                ],
                'ingredients' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string', 'description' => '材料名'],
                            'quantity' => [
                                'type' => 'string',
                                'nullable' => true,
                                'description' => '分量'
                            ],
                            'group' => [
                                'type' => 'string',
                                'nullable' => true,
                                'description' => '材料のグループ（例: 具材, 調味料, トッピング）。分類不可ならnull',
                            ],
                            'order' => ['type' => 'integer', 'description' => '表示順'],
                        ],
                        'required' => ['name', 'order'], // orderも必須に含めるのが安全
                    ],
                ],
                'dish_name' => [
                    'type' => 'string',
                    'description' => '料理の名前だけ。シンプルで一般的な名前にして。',
                ],
                'dish_slug' => [
                    'type' => 'string',
                    'description' => '料理名のスラッグ（英数字とハイフンのみ）',
                ],
                'steps' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'step_number' => ['type' => 'integer'],
                            'start_time_in_seconds' => [
                                'type' => 'integer',
                                'description' => '手順の開始時間（秒）',
                            ],
                            'end_time_in_seconds' => [
                                'type' => 'integer',
                                'nullable' => true, // 配列形式を修正
                                'description' => '手順の終了時間（秒）。不明な場合はnull',
                            ],
                            'description' => ['type' => 'string', 'description' => '手順の説明'],
                        ],
                        'required' => ['step_number', 'description', 'start_time_in_seconds'],
                    ],
                ],
                'tips' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'description' => ['type' => 'string', 'description' => '特に大事なコツやポイントを最大5つまで'],
                            'related_step_number' => [
                                'type' => 'integer',
                                'nullable' => true
                            ],
                            'start_time_in_seconds' => [
                                'type' => 'integer',
                                'nullable' => true,
                                'description' => 'コツが紹介される開始時間。不明な場合はnull',
                            ],
                        ],
                        'required' => ['description'],
                    ],
                ],
            ],
            'required' => ['is_recipe', 'title', 'ingredients', 'steps', 'dish_name', 'dish_slug'],
        ];
    }
}
