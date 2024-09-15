<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Plant;
class ScheduleWateringForPlants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:schedule-watering-for-plants';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedules watering events for all plants for the upcoming week';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $plants = Plant::all();

        foreach ($plants as $plant) {
            $plant->scheduleWateringEvents();
        }

        $this->info('Watering schedules for all plants have been updated.');
    }
}
