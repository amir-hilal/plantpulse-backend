<?php

namespace App\Http\Controllers;

use App\Models\PlantTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PlantTimelineController extends Controller
{
    public function index($plantId, Request $request)
    {
        $perPage = $request->get('perPage', 5);
        $page = $request->get('page', 1);

        $timelines = PlantTimeline::where('plant_id', $plantId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($timelines);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'plant_id' => 'required|exists:plants,id',
            'description' => 'nullable|string',
            'image_path' => 'nullable|string', // Modify this to handle image uploads
        ]);

        // Store the timeline event
        $timeline = PlantTimeline::create($validated);

        // Get the last 5 timeline events
        $recentTimelines = PlantTimeline::where('plant_id', $request->plant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Prepare the data to send to GPT-4 service
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant for plant care.',
            ],
        ];

        // Add recent timeline events to the GPT-4 conversation
        foreach ($recentTimelines as $event) {
            $messages[] = [
                'role' => 'user',
                'content' => $event->description ?? 'No description',
            ];
        }

        // Add the current user message
        $messages[] = [
            'role' => 'user',
            'content' => $request->description ?? 'No description',
        ];

        // Send data to the GPT-4 service
        $response = Http::post('https://openai-service.vercel.app/api/openai/chat', [
            'messages' => $messages,
        ]);

        // Handle the GPT-4 response
        if ($response->successful()) {
            $assistantResponse = $response->json()['choices'][0]['message']['content'];

            // Optionally, store the assistant's response in the timeline or return it to the frontend
            return response()->json([
                'timeline' => $timeline,
                'assistant_response' => $assistantResponse,
            ], 201);
        }

        return response()->json(['message' => 'Failed to get response from GPT-4 service'], 500);
    }

    public function destroy($id)
    {
        $timeline = PlantTimeline::findOrFail($id);
        $timeline->delete();
        return response()->json(['message' => 'Timeline entry deleted successfully']);
    }
}
