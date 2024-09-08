<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Plant;
use App\Models\PlantTimeline;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlantTimeline>
 */
class PlantTimelineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plant_id' => Plant::inRandomOrder()->first()->id,
            'description' => $this->faker->sentence,
            'image_path' => null, // this can be updated later when the user uploads an image
        ];
    }
}
