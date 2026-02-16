<?php

namespace Database\Factories;

use App\Models\Dish;
use App\Models\Recipe;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * モデルのデフォルト状態を定義します。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->realText(20) . 'レシピ';

        return [
            'video_id' => Video::factory(),
            'dish_id' => null,
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(5),
            'summary' => $this->faker->realText(100),
            'serving_size' => $this->faker->randomElement(['1人前', '2人前', '3~4人前']),
            'cooking_time' => $this->faker->randomElement(['10分', '15分', '30分', '1時間']),
        ];
    }

    /**
     * 特定の料理(Dish)に関連付ける状態
     */
    public function withDish(): static
    {
        return $this->state(fn(array $attributes) => [
            'dish_id' => Dish::factory(),
        ]);
    }
}
