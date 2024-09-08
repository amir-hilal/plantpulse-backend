<?php

namespace Database\Factories;

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
            'name' => $this->fake()->name(),
            'location' => $this->fake()->address(),
            'image_url' => $this->fake()->imageUrl(400, 400, 'people', true, 'Faker'),
            'user_id' => 1,
        ];
    }
}
