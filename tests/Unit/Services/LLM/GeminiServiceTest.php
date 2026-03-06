<?php

use App\Services\LLM\GeminiService;
use App\Services\LLM\LLMServiceInterface;
use App\Services\Schemas\RecipeSchema;
use App\Dtos\GeminiGenerateResultData;
use App\Exceptions\GeminiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

describe('GeminiService', function () {
    beforeEach(function () {
        Config::set('services.gemini.api_key', 'test-key');
        Config::set('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta/models/');
        Config::set('services.gemini.model', 'gemini-test-model');
    });

    test('it implements LLMServiceInterface', function () {
        $service = new GeminiService();
        expect($service)->toBeInstanceOf(LLMServiceInterface::class);
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
            'modelVersion' => 'gemini-1.5-flash'
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($mockResponse, 200),
        ]);

        $service = new GeminiService();

        $result = $service->generateStructured(
            'Prompt',
            RecipeSchema::get(),
            'Instruction',
            'https://example.com/video.mp4'
        );

        expect($result)->toBeInstanceOf(GeminiGenerateResultData::class);

        $data = $result->getData();
        expect($data['title'])->toBe('Delicious Curry');
        expect($data['is_recipe'])->toBeTrue();

        expect($result->getMetadata()['model_version'])->toBe('gemini-1.5-flash');
    });

    test('throws GeminiException on api failure', function () {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 429,
                    'message' => 'Quota exceeded',
                    'status' => 'RESOURCE_EXHAUSTED'
                ]
            ], 429),
        ]);

        $service = new GeminiService();

        expect(fn() => $service->generateStructured('P', [], 'I', 'U'))
            ->toThrow(GeminiException::class);
    });
});
