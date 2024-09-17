<?php

namespace Database\Factories;
use App\Models\Garden;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Garden>
 */
class GardenFactory extends Factory
{

    protected $model = Garden::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'location' => fake()->address(),
            'image_url' => fake()->imageUrl(400, 400, 'people', true, 'Faker'),
            'user_id' => 1,
        ];
    }
}
