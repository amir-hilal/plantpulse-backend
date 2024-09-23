<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Plant;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
        Log::info('ScheduleWateringForPlants command started at ' . now());

        $plants = Plant::all();

        foreach ($plants as $plant) {

            if ($plant->next_time_to_water) {
                try {
                    $nextWateringTime = Carbon::parse($plant->next_time_to_water);
                } catch (\Exception $e) {
                    Log::error('Invalid date format for plant ID ' . $plant->id . ': ' . $plant->next_time_to_water);
                    continue;
                }

                if ($nextWateringTime->isToday() || $nextWateringTime->isFuture()) {
                    $plant->sendWateringReminder();
                } else {
                    Log::info('No watering needed today for plant ID: ' . $plant->id);
                }
            } else {
                Log::info('No valid watering date set for plant ID: ' . $plant->id);
            }
        }

        Log::info('ScheduleWateringForPlants command completed at ' . now());

        $this->info('Watering schedules for all plants have been updated and reminders sent.');
    }
}
