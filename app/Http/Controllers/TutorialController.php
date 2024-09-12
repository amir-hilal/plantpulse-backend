<?php
namespace App\Http\Controllers;

use App\Models\Tutorial;
use Illuminate\Http\Request;

class TutorialController extends Controller
{
    // Fetch all tutorials with pagination (10 per page)
    public function index()
    {
        $tutorials = Tutorial::paginate(10);
        return response()->json($tutorials);
    }

    // Fetch a specific tutorial by ID, along with its comments
    public function show($id)
    {
        $tutorial = Tutorial::with('comments')->findOrFail($id);
        return response()->json($tutorial);
    }

    // Store a new tutorial (Admin only)
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'video_url' => 'required|string',
            'thumbnail_url' => 'required|string',
            'tags' => 'nullable|array',  // Expecting tags to be an array
        ]);

        $data['tags'] = json_encode($data['tags']);  // Convert tags array to JSON

        $tutorial = Tutorial::create($data);

        return response()->json($tutorial, 201);  // 201 Created status
    }

    // Update an existing tutorial (Admin only)
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'video_url' => 'sometimes|required|string',
            'thumbnail_url' => 'sometimes|required|string',
            'tags' => 'nullable|array',
        ]);

        $tutorial = Tutorial::findOrFail($id);

        if (isset($data['tags'])) {
            $data['tags'] = json_encode($data['tags']);
        }

        $tutorial->update($data);

        return response()->json($tutorial);
    }

    // Delete a tutorial (Admin only)
    public function destroy($id)
    {
        $tutorial = Tutorial::findOrFail($id);
        $tutorial->delete();

        return response()->json(['message' => 'Tutorial deleted successfully.'], 200);
    }
}
