<?php

use App\Services\LLM\GeminiService;
use App\Dtos\LLMResponseData;
use App\Exceptions\GeminiException;
use App\Infrastructure\Gemini\GeminiApiClient;
use App\Services\LLM\Schemas\RecipeSchema;
use Illuminate\Support\Facades\Http;

describe('GeminiService', function () {
    beforeEach(function () {
        $this->client = new GeminiApiClient(
            baseUrl: 'https://gemini.api.mock/',
            apiKey: 'dummy',
            model: 'gemini-2-flash'
        );

        $this->service = new GeminiService($this->client);
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

        $result = $this->service->generateStructured(
            'Prompt',
            RecipeSchema::get(),
            'Instruction',
            'https://example.com/video.mp4'
        );

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

        expect(fn() => $this->service->generateStructured('P', [], 'I', 'U'))
            ->toThrow(GeminiException::class);
    });
});
