<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Channel>
 */
class ChannelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel_id' => 'UC' . $this->faker->unique()->bothify('**********************'), // UCから始まる24文字
            'name' => $this->faker->name . ' Channel',
            'description' => $this->faker->paragraph,
            'thumbnail_url' => $this->faker->imageUrl(200, 200, 'people'),
            'custom_url' => '@' . $this->faker->userName,
            'subscriber_count' => $this->faker->numberBetween(100, 1000000),
            'view_count' => $this->faker->numberBetween(1000, 10000000),
            'video_count' => $this->faker->numberBetween(10, 500),
        ];
    }
}
