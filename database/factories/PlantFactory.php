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
            'garden_id' => Garden::inRandomOrder()->first()->id, // assuming gardens are already seeded
            'name' => $this->faker->word,
            'category' => $this->faker->randomElement(['flower', 'vegetable', 'herb']),
            'age' => $this->faker->numberBetween(1, 100),
            'important_note' => $this->faker->sentence,
            'last_watered' => $this->faker->date(),
            'image_url' => fake()->imageUrl(400, 400, 'people', true, 'Faker'),
            'next_time_to_water' => $this->faker->date(),
            'height' => $this->faker->randomFloat(2, 10, 150), // height in cm
            'health_status' => $this->faker->randomElement(['Healthy', 'Unhealthy', 'Diseased', 'Recovering']),
        ];
    }
}
