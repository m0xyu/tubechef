<?php

use App\Dtos\LLMRequestData;
use App\Dtos\LLMResponseData;
use App\Exceptions\RecipeException;
use App\Services\LLM\GoLLMService;
use Illuminate\Support\Facades\Http;

describe('GoLLMService', function () {
    beforeEach(function () {
        $this->service = new GoLLMService('http://ai-recipe-service.mock:3000');

        $this->request = new LLMRequestData(
            videoId: 'abc123',
            title: '鶏の唐揚げ レシピ',
            description: '鶏もも肉 300g、醤油 大さじ2',
            duration: 480,
            videoUrl: 'https://www.youtube.com/watch?v=abc123',
        );
    });

    test('正常なレスポンスをLLMResponseDataにマッピングする', function () {
        $recipeData = [
            'is_recipe' => true,
            'title' => '鶏の唐揚げ',
            'dish_name' => '鶏の唐揚げ',
            'dish_slug' => 'chicken-karaage',
            'ingredients' => [
                ['name' => '鶏もも肉', 'quantity' => '300g', 'group' => '具材', 'order' => 1],
            ],
            'steps' => [
                ['step_number' => 1, 'description' => '鶏肉を切る', 'start_time_in_seconds' => 0],
            ],
            'tips' => [],
        ];

        Http::fake([
            'http://ai-recipe-service.mock:3000/generate' => Http::response([
                'success' => true,
                'message' => 'レシピを生成しました',
                'data' => [
                    'recipe' => $recipeData,
                    'metadata' => [
                        'model_version' => 'gemini-2.5-flash',
                        'finish_reason' => 'STOP',
                        'usage' => [
                            'prompt_token_count' => 100,
                            'candidates_token_count' => 200,
                            'total_token_count' => 300,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->generate($this->request);

        expect($result)->toBeInstanceOf(LLMResponseData::class);
        expect($result->data['is_recipe'])->toBeTrue();
        expect($result->data['title'])->toBe('鶏の唐揚げ');
        expect($result->model)->toBe('gemini-2.5-flash');
        expect($result->metadata['finish_reason'])->toBe('STOP');
        expect($result->metadata['usage']['total_token_count'])->toBe(300);
    });

    test('Goサービスがエラーを返した場合RecipeExceptionをスローする', function () {
        Http::fake([
            'http://ai-recipe-service.mock:3000/generate' => Http::response([
                'success' => false,
                'error_code' => 'generation_failed',
                'message' => 'AI生成に失敗しました',
            ], 500),
        ]);

        expect(fn() => $this->service->generate($this->request))
            ->toThrow(RecipeException::class);
    });

    test('Goサービスに接続できない場合RecipeExceptionをスローする', function () {
        Http::fake([
            'http://ai-recipe-service.mock:3000/generate' => Http::response([], 503),
        ]);

        expect(fn() => $this->service->generate($this->request))
            ->toThrow(RecipeException::class);
    });
});
