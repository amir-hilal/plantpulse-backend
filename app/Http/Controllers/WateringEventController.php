<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use App\Models\WateringEvent;
use Illuminate\Http\Request;

class WateringEventController extends Controller
{
    public function index(Plant $plant)
    {
        return $plant->wateringEvents;
    }
    public function markComplete(Request $request, $plantId, $eventId)
    {
        // Fetch the watering event by ID and load the related plant
        $event = WateringEvent::with('plant:id,name')->findOrFail($eventId);

        // Toggle the 'is_done' status
        $event->update([
            'is_done' => !$event->is_done,
            'completed_at' => !$event->is_done ? now() : null,
        ]);

        // Add the plant name directly inside the event object
        $event->plant_name = $event->plant->name;

        // Unset the plant relation to avoid returning full plant data
        unset($event->plant);

        return response()->json([
            'message' => 'Watering status updated',
            'event' => $event,
        ], 200);
    }

    public function getUserWateringSchedules(Request $request)
    {
        $user = $request->user();

        // Fetch all watering events for plants in user's gardens along with plant names
        $wateringEvents = $user->gardens()
            ->with('plants.wateringEvents.plant')
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
