<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'profile_photo_url' => fake()->imageUrl(400, 400, 'people', true, 'Faker'),
            'cover_photo_url' => fake()->optional()->imageUrl(1200, 400, 'nature', true, 'Faker'),
            'about' => fake()->optional()->paragraph(),
            'phone_number' => fake()->optional()->phoneNumber(),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'birthday' => fake()->optional()->date(),
            'address' => fake()->optional()->address(),
            'google_id' => fake()->optional()->uuid(),
            'role' => fake()->randomElement(['user', 'admin']),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
