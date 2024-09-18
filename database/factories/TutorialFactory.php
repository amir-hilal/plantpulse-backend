<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tutorial>
 */
class TutorialFactory extends Factory
{
    protected $validYouTubeLinks = [
        'https://www.youtube.com/watch?v=tbmUvgr28Oo',
        'https://www.youtube.com/watch?v=pLQuIuokP6Q&t=1s',
        'https://www.youtube.com/watch?v=3_Wt0O9--34',
        'https://www.youtube.com/watch?v=jEt_120VEAM',
        'https://www.youtube.com/watch?v=NlS_dTDsHHQ',
        'https://www.youtube.com/watch?v=MWM7ZAkee1s',
        'https://www.youtube.com/watch?v=Yzdabta1nAA',
        'https://www.youtube.com/watch?v=nxTzuasQLFo',
        'https://www.youtube.com/watch?v=PA-b1rQ42vU',
        'https://www.youtube.com/watch?v=4hHi0Xs1bHA',
        'https://www.youtube.com/watch?v=koHEvJL1OKI',
    ];
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
            'video_url' => $this->validYouTubeLinks[array_rand($this->validYouTubeLinks)],
            'tags' => json_encode($this->faker->words(5)),  // Generate random tags
        ];
    }
}
