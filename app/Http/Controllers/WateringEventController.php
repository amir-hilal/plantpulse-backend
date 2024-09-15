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

    public function markComplete(Request $request, Plant $plant, WateringEvent $event)
    {
        $event->update([
            'is_done' => true,
            'completed_at' => now(),
        ]);

        return response()->json(['message' => 'Watering marked as complete']);
    }
}
