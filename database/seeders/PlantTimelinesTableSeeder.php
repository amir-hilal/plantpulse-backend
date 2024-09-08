<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlantTimelinesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plant::all()->each(function ($plant) {
            PlantTimeline::factory()->count(5)->create([
                'plant_id' => $plant->id,
            ]);
        });
    }
}
