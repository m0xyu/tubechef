<?php

use App\Actions\GenerateRecipeAction;
use App\Enums\Errors\RecipeError;
use App\Exceptions\RecipeException;
use App\Models\Recipe;
use App\Models\Video;
use App\Services\LLM\LLMServiceFactory;
use App\Services\LLM\LLMServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

describe('GenerateRecipeActionTest', function () {
    beforeEach(function () {
        Config::set('services.gemini.api_key', 'test-key');
        Config::set('services.gemini.base_url', 'https://example.com/');
        Config::set('services.gemini.model', 'gemini-test-model');
    });

    test('レシピが正常に保存される', function () {
        $mock = Mockery::mock(LLMServiceInterface::class);
        $data = [
            'is_recipe' => true,
            'title' => 'Delicious Curry',
            'ingredients' => [
                ['name' => 'Chicken', 'quantity' => '200g', 'group' => 'Meat', 'order' => 1],
            ],
            'steps' => [
                ['step_number' => 1, 'description' => 'Cut the chicken.', 'start_time_in_seconds' => 0],
            ],
            'tips' => [],
            'dish_name' => 'Curry',
            'dish_slug' => 'curry',
        ];

        // 1. APIの階層構造を模倣し、JSON文字列として格納
        $wrappedContent = [
            'parts' => [
                ['text' => json_encode($data)]
            ]
        ];

        $usage = new \App\ValueObjects\GeminiUsageMetadata(0, 0, 0);
        $candidate = new \App\ValueObjects\GeminiResponseCandidate($wrappedContent, 'STOP', $usage, 'test-model');
        $geminiResult = new \App\Dtos\GeminiGenerateResultData($candidate);

        $mock->shouldReceive('generateStructured')
            ->once()
            ->with(Mockery::any(), Mockery::type('array'), Mockery::type('string'), Mockery::type('string'))
            ->andReturn($geminiResult);

        $factoryMock = Mockery::mock(LLMServiceFactory::class);
        $factoryMock->shouldReceive('make')->andReturn($mock);
        app()->instance(LLMServiceFactory::class, $factoryMock);

        // 必要に応じてモデルの保存先などを注入（RecipeServiceなど）

        $video = Video::factory()->create();
        $action = app(GenerateRecipeAction::class);

        // 2. 実行（戻り値は Recipe モデル）
        $recipe = $action->execute($video);

        expect($recipe)->toBeInstanceOf(Recipe::class);
        expect($recipe->title)->toBe('Delicious Curry');
        expect($recipe->video_id)->toBe($video->id);
    });

    test('is_recipeがfalseの場合RecipeExceptionが投げられる', function () {
        $mock = Mockery::mock(LLMServiceInterface::class);
        $data = [
            'is_recipe' => false,
            'title' => 'Delicious Curry',
            'ingredients' => [
                ['name' => 'Chicken', 'quantity' => '200g', 'group' => 'Meat', 'order' => 1],
            ],
            'steps' => [
                ['step_number' => 1, 'description' => 'Cut the chicken.', 'start_time_in_seconds' => 0],
            ],
            'tips' => [],
            'dish_name' => 'Curry',
            'dish_slug' => 'curry',
        ];

        // 1. APIの階層構造を模倣し、JSON文字列として格納
        $wrappedContent = [
            'parts' => [
                ['text' => json_encode($data)]
            ]
        ];

        $usage = new \App\ValueObjects\GeminiUsageMetadata(0, 0, 0);
        $candidate = new \App\ValueObjects\GeminiResponseCandidate($wrappedContent, 'STOP', $usage, 'test-model');
        $geminiResult = new \App\Dtos\GeminiGenerateResultData($candidate);

        $mock->shouldReceive('generateStructured')
            ->once()
            ->with(Mockery::any(), Mockery::type('array'), Mockery::type('string'), Mockery::type('string'))
            ->andReturn($geminiResult);

        $factoryMock = Mockery::mock(LLMServiceFactory::class);
        $factoryMock->shouldReceive('make')
            ->andReturn($mock);

        app()->instance(LLMServiceFactory::class, $factoryMock);

        $video = Video::factory()->create();
        $action = app(GenerateRecipeAction::class);
        expect(fn() => $action->execute($video))
            ->toThrow(RecipeException::class, RecipeError::NOT_A_RECIPE->message());
    });
});
