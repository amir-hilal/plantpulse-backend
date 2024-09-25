<?php

namespace Database\Seeders;
use App\Models\Garden;
use App\Models\User;
use App\Models\Friend;
use App\Models\PostComment;
use App\Models\CommunityPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a user for the Garden seeder
        User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin_user',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'profile_photo_url' => fake()->imageUrl(400, 400, 'people', true, 'Faker'),
            'cover_photo_url' => fake()->optional()->imageUrl(1200, 400, 'nature', true, 'Faker'),
            'about' => 'Administrator of the system',
            'phone_number' => fake()->optional()->phoneNumber(),
            'gender' => 'male',
            'birthday' => fake()->optional()->date(),
            'address' => fake()->optional()->address(),
            'google_id' => fake()->optional()->uuid(),
            'role' => 'admin',  // Role is set to 'admin'
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        // Seed additional users
        User::factory(50)->create();

        // Create relationships
        Friend::factory(10)->create();
        CommunityPost::factory(10)->create();
        PostComment::factory(10)->create();

        // Create gardens for existing users
        $users = User::all();
        Garden::factory(5)->create([
            'user_id' => $users->random()->id, // Randomly assign a user
        ]);

        // Call other seeders
        $this->call(PlantsTableSeeder::class);
        $this->call(PlantTimelinesTableSeeder::class);
        $this->call(TutorialSeeder::class);
        $this->call(TutorialCommentSeeder::class);
    }
}
