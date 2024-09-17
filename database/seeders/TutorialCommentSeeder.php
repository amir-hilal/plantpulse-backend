<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tutorial;
use App\Models\TutorialComment;

class TutorialCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tutorial::all()->each(function ($tutorial) {
            TutorialComment::factory()->count(3)->create([
                'tutorial_id' => $tutorial->id,    // Set the tutorial_id for each comment
            ]);
        });
    }
}
