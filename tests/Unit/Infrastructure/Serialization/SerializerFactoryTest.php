<?php

use App\Dtos\GeneratedRecipeData;
use App\Infrastructure\Serialization\SerializerFactory;

beforeEach(function () {
    $this->serializer = SerializerFactory::create();
});

test('create returns a SerializerInterface instance', function () {
    expect($this->serializer)->toBeInstanceOf(\Symfony\Component\Serializer\SerializerInterface::class);
});

test("it_can_denormalize_json_to_dto", function () {
    $data = [
        'is_recipe' => true,
        'title' => 'Delicious Curry',
        'summary' => 'A tasty curry recipe.',
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

    $dto = $this->serializer->denormalize($data, GeneratedRecipeData::class);
    expect($dto)->toBeInstanceOf(GeneratedRecipeData::class);
    expect($dto->isRecipe)->toBe(true);
    expect($dto->title)->toBe('Delicious Curry');
    expect($dto->summary)->toBe('A tasty curry recipe.');
    expect($dto->ingredients)->toHaveCount(1);
    expect($dto->ingredients[0]->name)->toBe('Chicken');
    expect($dto->steps)->toHaveCount(1);
    expect($dto->steps[0]->description)->toBe('Cut the chicken.');
    expect($dto->tips)->toBeEmpty();
    expect($dto->dishName)->toBe('Curry');
    expect($dto->dishSlug)->toBe('curry');
});
