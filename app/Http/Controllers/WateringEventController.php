<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use App\Models\WateringEvent;
use Illuminate\Http\Request;
use Carbon\Carbon;
class WateringEventController extends Controller
{
    public function index(Plant $plant)
    {
        return $plant->wateringEvents;
    }
    public function markComplete(Request $request, $plantId, $eventId)
    {
        $event = WateringEvent::with('plant:id,name')->findOrFail($eventId);

        if (Carbon::now()->toDateString() > Carbon::parse($event->scheduled_date)->toDateString()) {
            return response()->json(['message' => 'Cannot mark as done, past the scheduled date'], 403);
        }
        $event->update([
            'is_done' => !$event->is_done,
            'completed_at' => !$event->is_done ? now() : null,
        ]);

        $event->plant_name = $event->plant->name;
        unset($event->plant);

        $this->updatePlantWateringStatus($event->plant, $event);

        return response()->json([
            'message' => 'Watering status updated',
            'event' => $event,
        ], 200);
    }

    private function updatePlantWateringStatus($plant, $event)
    {
        if ($event->is_done) {
            // Mark as done, update next_time_to_water and last_watered
            $plant->update([
                'last_watered' => $event->completed_at,
                'next_time_to_water' => $plant->wateringEvents()
                    ->where('is_done', false)
                    ->orderBy('scheduled_date', 'asc')
                    ->first()->scheduled_date
            ]);
        } else {
            // Undo, revert last_watered and next_time_to_water
            $previousEvent = $plant->wateringEvents()
                ->where('is_done', true)
                ->orderBy('completed_at', 'desc')
                ->first();

            $plant->update([
                'last_watered' => $previousEvent ? $previousEvent->completed_at : null,
                'next_time_to_water' => $plant->wateringEvents()
                    ->where('is_done', false)
                    ->orderBy('scheduled_date', 'asc')
                    ->first()->scheduled_date
            ]);
        }
    }


    public function getUserTodayWateringSchedules(Request $request)
    {
        $user = $request->user();

        // Fetch all watering events for plants in user's gardens along with plant names
        $wateringEvents = $user->gardens()
            ->with([
                'plants.wateringEvents' => function ($query) {
                    $query->whereDate('scheduled_date', Carbon::today());
                }
            ])
            ->get()
            ->pluck('plants')
            ->flatten()
            ->pluck('wateringEvents')
            ->flatten()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'scheduled_date' => $event->scheduled_date,
                    'is_done' => $event->is_done,
                    'completed_at' => $event->completed_at,
                    'plant_id' => $event->plant->id,
                    'plant_name' => $event->plant->name,
                ];
            });

        return response()->json($wateringEvents);
    }


}
