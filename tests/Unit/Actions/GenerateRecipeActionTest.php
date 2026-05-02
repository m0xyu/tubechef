<?php

use App\Actions\GenerateRecipeAction;
use App\Enums\Errors\RecipeError;
use App\Exceptions\RecipeException;
use App\Models\Recipe;
use App\Models\Video;
use App\Services\LLM\LLMServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('GenerateRecipeActionTest', function () {

    test('レシピが正常に保存される', function () {
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

        $geminiResult = new \App\Dtos\LLMResponseData(
            data: $data,
            model: 'test-model',
            usage: ['prompt_tokens' => 10, 'completion_tokens' => 20], // 具体的な数値
            rawContent: json_encode($data)
        );

        $this->mock(LLMServiceInterface::class, function ($mock) use ($geminiResult) {
            $mock->shouldReceive('generateStructured')
                ->once()
                ->with(Mockery::any(), Mockery::type('array'), Mockery::type('string'), Mockery::type('string'))
                ->andReturn($geminiResult);
        });

        $video = Video::factory()->create();
        $action = app(GenerateRecipeAction::class);
        $recipe = $action->execute($video);

        expect($recipe)->toBeInstanceOf(Recipe::class);
        expect($recipe->title)->toBe('Delicious Curry');
        expect($recipe->video_id)->toBe($video->id);
    });

    test('is_recipeがfalseの場合RecipeExceptionが投げられる', function () {
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

        $wrappedContent = [
            'parts' => [
                ['text' => json_encode($data)]
            ]
        ];

        $geminiResult = new \App\Dtos\LLMResponseData(
            data: $data,
            model: 'test-model',
            usage: ['token_count' => 10],
            rawContent: json_encode($wrappedContent)
        );

        $this->mock(LLMServiceInterface::class, function ($mock) use ($geminiResult) {
            $mock->shouldReceive('generateStructured')
                ->once()
                ->with(Mockery::any(), Mockery::type('array'), Mockery::type('string'), Mockery::type('string'))
                ->andReturn($geminiResult);
        });


        $video = Video::factory()->create();
        $action = app(GenerateRecipeAction::class);
        try {
            $action->execute($video);
            $this->fail('RecipeException was not thrown.');
        } catch (RecipeException $e) {
            expect($e->getErrorEnum())->toBe(RecipeError::NOT_A_RECIPE);
        }
    });
});
