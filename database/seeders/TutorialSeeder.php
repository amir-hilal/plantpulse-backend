<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tutorial;
use App\Models\TutorialComment;
class TutorialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tutorial::factory(10)->create()->each(function ($tutorial) {
            TutorialComment::factory(9)->create([
                'tutorial_id' => $tutorial->id,
            ]);
        });
    }
}
