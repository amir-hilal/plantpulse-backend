<?php
namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    // Get all plants for a specific garden
    public function index($gardenId)
    {
        $plants = Plant::where('garden_id', $gardenId)->get();
        return response()->json($plants);
    }

    // Store a new plant
    public function store(Request $request)
    {
        $validated = $request->validate([
            'garden_id' => 'required|exists:gardens,id',
            'name' => 'required|string',
            'category' => 'required|string',
            'age' => 'required|integer',
            'important_note' => 'nullable|string',
            'last_watered' => 'nullable|date',
            'next_time_to_water' => 'nullable|date',
            'height' => 'nullable|numeric',
            'health_status' => 'required|string',
        ]);

        $plant = Plant::create($validated);
        return response()->json($plant, 201);
    }

    // Show a single plant
    public function show($id)
    {
        $plant = Plant::findOrFail($id);
        return response()->json($plant);
    }

    // Update a plant
    public function update(Request $request, $id)
    {
        $plant = Plant::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string',
            'category' => 'string',
            'age' => 'integer',
            'important_note' => 'nullable|string',
            'last_watered' => 'nullable|date',
            'next_time_to_water' => 'nullable|date',
            'height' => 'nullable|numeric',
            'health_status' => 'string',
        ]);

        $plant->update($validated);
        return response()->json($plant);
    }

    // Delete a plant
    public function destroy($id)
    {
        $plant = Plant::findOrFail($id);
        $plant->delete();
        return response()->json(['message' => 'Plant deleted successfully']);
    }
}
