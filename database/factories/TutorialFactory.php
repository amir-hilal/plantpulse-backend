<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tutorial>
 */
class TutorialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'video_url' => $this->faker->url,
            'thumbnail_url' => $this->faker->imageUrl(640, 480, 'nature'),
            'tags' => json_encode($this->faker->words(5)),  // Generate random tags
            'views' => $this->faker->numberBetween(0, 1000),
        ];
    }
}
