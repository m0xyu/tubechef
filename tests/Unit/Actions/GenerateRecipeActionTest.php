<?php

use App\Actions\GenerateRecipeAction;
use App\Enums\Errors\RecipeError;
use App\Exceptions\RecipeException;
use App\Models\Dish;
use App\Models\Video;
use App\Services\LLM\LLMServiceFactory;
use App\Services\LLM\LLMServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('GenerateRecipeActionTest', function () {
    test('レシピが正常に保存される', function () {
        $mock = Mockery::mock(LLMServiceInterface::class);
        $data = [
            'is_recipe' => true,
            'title' => 'Delicious Curry',
            'ingredients' => [
                ['name' => 'Chicken', 'quantity' => '200g', 'group' => 'Meat'],
                ['name' => 'Onion', 'quantity' => '1', 'group' => 'Vegetable']
            ],
            'steps' => [
                ['step_number' => 1, 'description' => 'Cut the chicken.', 'start_time_in_seconds' => 0],
                ['step_number' => 2, 'description' => 'Chop the onion.', 'start_time_in_seconds' => 30],
            ],
            'tips' => [
                ['description' => 'Use fresh chicken for better taste.', 'related_step_number' => 1, 'start_time_in_seconds' => 0],
            ],
            'dish_name' => 'Curry',
            'dish_slug' => 'curry',
        ];

        $mock->shouldReceive('generateRecipe')
            ->once()
            ->andReturn(\App\Dtos\GeneratedRecipeData::fromArray($data));

        $factoryMock = Mockery::mock(LLMServiceFactory::class);
        $factoryMock->shouldReceive('make')
            ->andReturn($mock);

        app()->instance(LLMServiceFactory::class, $factoryMock);

        $dish = Dish::factory()->create([
            'name' => 'Curry',
            'slug' => 'curry',
        ]);

        $video = Video::factory()->create();
        $action = app(GenerateRecipeAction::class);
        $result = $action->execute($video);

        expect($result->title)->toBe('Delicious Curry');
        expect($result->ingredients)->toHaveCount(2);
        expect($result->steps)->toHaveCount(2);
        expect($result->tips)->toHaveCount(1);
        expect($result->dish_id)->toBe($dish->id);
    });

    test('is_recipeがfalseの場合RecipeExceptionが投げられる', function () {
        $mock = Mockery::mock(LLMServiceInterface::class);
        $data = [
            'is_recipe' => false,
            'title' => 'Delicious Curry',
            'ingredients' => [
                ['name' => 'Chicken', 'quantity' => '200g', 'group' => 'Meat'],
                ['name' => 'Onion', 'quantity' => '1', 'group' => 'Vegetable']
            ],
            'steps' => [
                ['step_number' => 1, 'description' => 'Cut the chicken.', 'start_time_in_seconds' => 0],
                ['step_number' => 2, 'description' => 'Chop the onion.', 'start_time_in_seconds' => 30],
            ],
            'tips' => [
                ['description' => 'Use fresh chicken for better taste.', 'related_step_number' => 1, 'start_time_in_seconds' => 0],
            ],
            'dish_name' => 'Curry',
            'dish_slug' => 'curry',
        ];

        $mock->shouldReceive('generateRecipe')
            ->once()
            ->andReturn(\App\Dtos\GeneratedRecipeData::fromArray($data));

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
