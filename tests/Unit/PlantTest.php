<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Plant;
use App\Models\Garden;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class PlantTest extends TestCase
{

    /** @test */
    public function it_can_create_a_plant()
    {
        DB::beginTransaction();

        // Create a garden first to associate with the plant
        $garden = Garden::factory()->create();

        $plant = Plant::create([
            'garden_id' => $garden->id,
            'name' => 'Test Plant',
            'category' => 'flower',
            'planted_date' => now(),
            'important_note' => 'This is a test note.',
            'last_watered' => now(),
            'image_url' => 'https://via.placeholder.com/400x400.png',
            'next_time_to_water' => now()->addDays(3),
            'height' => 30.5,
            'health_status' => 'Healthy',
            'watering_frequency' => 3,
        ]);

        $this->assertDatabaseHas('plants', [
            'name' => 'Test Plant',
            'garden_id' => $garden->id,
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_can_update_a_plant()
    {
        DB::beginTransaction();

        $plant = Plant::factory()->create([
            'name' => 'Old Plant Name',
        ]);

        $plant->update(['name' => 'Updated Plant Name']);

        $this->assertEquals('Updated Plant Name', $plant->fresh()->name);

        DB::rollBack();
    }

    /** @test */
    public function it_can_delete_a_plant()
    {
        DB::beginTransaction();

        $plant = Plant::first();

        $plant->delete();

        $this->assertSoftDeleted('plants', [
            'id' => $plant->id,
        ]);

        DB::rollBack();
    }

}
