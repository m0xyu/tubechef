<?php

namespace Database\Factories;

use App\Models\Channel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Video>
 */
class VideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'video_id' => $this->faker->unique()->bothify('???????????'), // 11桁のランダムID
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'thumbnail_url' => $this->faker->imageUrl(),
            'channel_id' => Channel::factory(),
            'published_at' => now(),
            'fetched_at' => now(),
            'recipe_generation_status' => \App\Enums\RecipeGenerationStatus::PENDING,
        ];
    }
}
