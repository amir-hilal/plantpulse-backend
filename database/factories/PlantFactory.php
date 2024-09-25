<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Garden;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plant>
 */
class PlantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'garden_id' => Garden::inRandomOrder()->first()->id, // Assuming gardens are already seeded
            'name' => $this->faker->word,
            'category' => $this->faker->randomElement(['flower', 'vegetable', 'herb']),
            'planted_date' => $this->faker->date(), // Add planted_date instead of age
            'important_note' => $this->faker->sentence,
            'last_watered' => $this->faker->date(),
            'image_url' => $this->faker->imageUrl(400, 400, 'people', true, 'Faker'),
            'next_time_to_water' => $this->faker->date(),
            'height' => $this->faker->randomFloat(2, 10, 150), // Height in cm
            'health_status' => $this->faker->randomElement(['Healthy', 'Unhealthy', 'Diseased', 'Recovering']),
            'watering_frequency' => $this->faker->numberBetween(1, 7) // Assuming watering frequency is a value between 1-7
        ];
    }
}
