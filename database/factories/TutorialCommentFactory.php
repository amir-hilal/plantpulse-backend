<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tutorial;
use App\Models\User;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TutorialComment>
 */
class TutorialCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tutorial_id' => Tutorial::factory(),
            'user_id' => User::factory(),
            'comment_text' => $this->faker->sentence,
        ];
    }
}
