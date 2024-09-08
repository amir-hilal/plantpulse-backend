<?php
namespace App\Http\Controllers;

use App\Models\PlantTimeline;
use Illuminate\Http\Request;

class PlantTimelineController extends Controller
{
    // Get all timeline entries for a specific plant
    public function index($plantId)
    {
        $timeline = PlantTimeline::where('plant_id', $plantId)->get();
        return response()->json($timeline);
    }

    // Store a new timeline entry
    public function store(Request $request)
    {
        $validated = $request->validate([
            'plant_id' => 'required|exists:plants,id',
            'description' => 'nullable|string',
            'image_path' => 'nullable|string', // You can change this later to handle actual file uploads
        ]);

        $timeline = PlantTimeline::create($validated);
        return response()->json($timeline, 201);
    }

    // Show a single timeline entry
    public function show($id)
    {
        $timeline = PlantTimeline::findOrFail($id);
        return response()->json($timeline);
    }

    // Update a timeline entry
    public function update(Request $request, $id)
    {
        $timeline = PlantTimeline::findOrFail($id);

        $validated = $request->validate([
            'description' => 'nullable|string',
            'image_path' => 'nullable|string',
        ]);

        $timeline->update($validated);
        return response()->json($timeline);
    }

    // Delete a timeline entry
    public function destroy($id)
    {
        $timeline = PlantTimeline::findOrFail($id);
        $timeline->delete();
        return response()->json(['message' => 'Timeline entry deleted successfully']);
    }
}
