<?php

namespace Database\Seeders;
use App\Models\Garden;
use App\Models\User;
use App\Models\Friend;
use App\Models\PostComment;
use App\Models\CommunityPost;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PlantsTableSeeder::class);
        $this->call(PlantTimelinesTableSeeder::class);

        User::factory(50)->create();
        Friend::factory(10)->create();
        CommunityPost::factory(10)->create();
        PostComment::factory(10)->create();
        Garden::factory()->count(5)->create([
            'user_id' => 1
        ]);
    }
}
