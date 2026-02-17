<?php

use App\Services\LLM\GeminiService;
use App\Services\LLM\LLMServiceInterface;
use Illuminate\Support\Facades\Http;

describe('GeminiService', function () {
    test('it implements LLMServiceInterface', function () {
        $service = new GeminiService();
        expect($service)->toBeInstanceOf(LLMServiceInterface::class);
    });

    test('valid response generates recipe data', function () {
        $mockResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'is_recipe' => true,
                                    'title' => 'Delicious Curry',
                                    'summary' => 'Spicy and tasty.',
                                    'serving_size' => '2 servings',
                                    'cooking_time' => '30 mins',
                                    'ingredients' => [
                                        ['name' => 'Chicken', 'quantity' => '200g', 'group' => 'Meat'],
                                        ['name' => 'Onion', 'quantity' => '1', 'group' => 'Vegetable']
                                    ],
                                    'steps' => [
                                        ['step_number' => 1, 'description' => 'Cut the chicken.']
                                    ],
                                    'tips' => []
                                ])
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($mockResponse, 200),
        ]);

        $service = new GeminiService();
        $result = $service->generateRecipe('Curry Video', 'How to make curry', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        expect($result->title)->toBe('Delicious Curry');
        expect($result->ingredients[0]->name)->toBe('Chicken');
        expect($result->isRecipe)->toBeTrue();
    });

    test('throws exception on api failure', function () {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'Internal Server Error'], 500),
        ]);

        $service = new GeminiService();

        expect(fn() => $service->generateRecipe('Video', 'Desc', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'))
            ->toThrow(Exception::class);
    });
});
