<?php
namespace App\Http\Controllers;

use App\Models\Garden;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GardenController extends Controller
{
    // Get all gardens for the authenticated user
    public function index()
    {
        $gardens = Garden::where('user_id', Auth::id())->get();
        return response()->json($gardens);
    }

    // Create a new garden
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $garden = Garden::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'location' => $request->location,
        ]);

        return response()->json($garden, 201);
    }

    // Show a specific garden
    public function show($id)
    {
        $garden = Garden::where('user_id', Auth::id())->findOrFail($id);
        return response()->json($garden);
    }

    // Update a garden
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $garden = Garden::where('user_id', Auth::id())->findOrFail($id);
        $garden->update($request->only('name', 'location'));

        return response()->json($garden);
    }

    // Delete a garden
    public function destroy($id)
    {
        $garden = Garden::where('user_id', Auth::id())->findOrFail($id);
        $garden->delete();

        return response()->json(null, 204);
    }
}
