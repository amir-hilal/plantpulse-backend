<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Garden;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class GardenTest extends TestCase
{


    /** @test */
    public function it_can_create_a_garden()
    {
        // Fetch an existing user from the database
        $user = User::first();

        // Ensure we have a user to work with
        $this->assertNotNull($user, 'No user found in the database.');

        DB::beginTransaction(); // Start a transaction

        $garden = Garden::create([
            'name' => 'My Garden',
            'location' => 'Backyard',
            'image_url' => 'https://via.placeholder.com/400x400.png/00bbdd?text=people+Faker+qui',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('gardens', [
            'name' => 'My Garden',
            'user_id' => $user->id,
        ]);

        DB::rollBack(); // Rollback the transaction
    }

    /** @test */
    public function it_can_update_a_garden()
    {
        DB::beginTransaction(); // Start a transaction

        $garden = Garden::first(); // Fetch an existing garden

        $garden->update(['name' => 'Updated Garden']);

        $this->assertEquals('Updated Garden', $garden->fresh()->name);

        DB::rollBack(); // Rollback the transaction
    }

    /** @test */
    public function it_can_delete_a_garden()
    {
        DB::beginTransaction();

        $garden = Garden::first();

        $garden->delete();

        $this->assertSoftDeleted('gardens', ['id' => $garden->id]);

        DB::rollBack();
    }
}
