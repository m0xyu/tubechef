<?php

use App\Services\LLM\GeminiService;
use App\Dtos\LLMRequestData;
use App\Dtos\LLMResponseData;
use App\Exceptions\GeminiException;
use App\Infrastructure\Gemini\GeminiApiClient;
use Illuminate\Support\Facades\Http;

describe('GeminiService', function () {
    beforeEach(function () {
        $this->client = new GeminiApiClient(
            baseUrl: 'https://gemini.api.mock/',
            apiKey: 'dummy',
            model: 'gemini-2-flash'
        );

        $this->service = new GeminiService($this->client);

        $this->request = new LLMRequestData(
            videoId: 'test123',
            title: 'テスト料理動画',
            description: '美味しいカレーの作り方',
            duration: 600,
            videoUrl: 'https://www.youtube.com/watch?v=test123',
        );
    });

    test('valid structured response generates result data', function () {
        $recipeData = [
            'is_recipe' => true,
            'title' => 'Delicious Curry',
            'ingredients' => [
                ['name' => 'Chicken', 'quantity' => '200g', 'order' => 1]
            ],
            'steps' => [
                ['step_number' => 1, 'description' => 'Cut the chicken.', 'start_time_in_seconds' => 0]
            ],
            'dish_name' => 'Curry',
            'dish_slug' => 'curry',
        ];

        $mockResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => json_encode($recipeData)]
                        ]
                    ],
                    'finishReason' => 'STOP',
                ]
            ],
            'usageMetadata' => [
                'promptTokenCount' => 100,
                'candidatesTokenCount' => 50,
                'totalTokenCount' => 150,
            ],
            'modelVersion' => 'gemini-2-flash'
        ];

        Http::fake([
            'https://gemini.api.mock/*' => Http::response($mockResponse, 200),
        ]);

        $result = $this->service->generate($this->request);

        expect($result)->toBeInstanceOf(LLMResponseData::class);

        $data = $result->data;
        expect($data['title'])->toBe('Delicious Curry');
        expect($data['is_recipe'])->toBeTrue();

        expect($result->model)->toBe('gemini-2-flash');
    });

    test('throws GeminiException on api failure', function () {
        Http::fake([
            'https://gemini.api.mock/*' => Http::response([
                'error' => [
                    'code' => 429,
                    'message' => 'Quota exceeded',
                    'status' => 'RESOURCE_EXHAUSTED'
                ]
            ], 429),
        ]);

        expect(fn() => $this->service->generate($this->request))
            ->toThrow(GeminiException::class);
    });
});

