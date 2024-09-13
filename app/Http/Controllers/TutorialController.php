<?php
namespace App\Http\Controllers;

use App\Models\Tutorial;
use Illuminate\Http\Request;
use Google_Client;
use Google_Service_YouTube;

class TutorialController extends Controller
{
    // Fetch all tutorials with pagination (10 per page)
    public function index(Request $request)
    {
        // Fetch tutorials from DB
        $tutorials = Tutorial::paginate(10);

        // Initialize Google Client
        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        $client->setAccessType('offline');
        $client->addScope(Google_Service_YouTube::YOUTUBE_READONLY);

        // Check if OAuth token is set
        if ($client->isAccessTokenExpired()) {
            // Redirect to authorization URL if token expired (or obtain a new one)
            return redirect()->away($client->createAuthUrl());
        }

        $youtube = new Google_Service_YouTube($client);

        // Loop through each tutorial and fetch YouTube data
        foreach ($tutorials as $tutorial) {
            if ($tutorial->video_url) {
                // Extract the video ID from the YouTube URL
                $videoId = $this->extractVideoId($tutorial->video_url);

                if ($videoId) {
                    try {
                        // Make API request to YouTube for video data
                        $response = $youtube->videos->listVideos('snippet,contentDetails,statistics', [
                            'id' => $videoId,
                        ]);

                        if (count($response->items) > 0) {
                            $videoData = $response->items[0];

                            // Update tutorial object with YouTube data
                            $tutorial->thumbnail_url = $videoData->snippet->thumbnails->default->url;
                            $tutorial->views = $videoData->statistics->viewCount;
                            $tutorial->duration = $this->convertISO8601Duration($videoData->contentDetails->duration);
                        }
                    } catch (\Exception $e) {
                        // Handle API errors
                        return response()->json(['error' => 'Failed to fetch video data.'], 500);
                    }
                }
            }
        }

        return response()->json($tutorials);
    }

    // Store a new tutorial (Admin only)
    public function store(Request $request)
    {
        // Validate user input
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'video_url' => 'required|url',
            'tags' => 'nullable|array',  // Expecting tags to be an array
        ]);

        // Save the tutorial data in the database
        $tutorial = Tutorial::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'video_url' => $data['video_url'],
            'tags' => json_encode($data['tags']), // Save tags as JSON
        ]);

        return response()->json($tutorial, 201); // 201 Created
    }

    // Fetch a specific tutorial by ID
    public function show($id)
    {
        $tutorial = Tutorial::findOrFail($id);

        // Fetch YouTube data
        if ($tutorial->video_url) {
            $videoId = $this->extractVideoId($tutorial->video_url);

            if ($videoId) {
                try {
                    $client = new Google_Client();
                    $client->setClientId(env('GOOGLE_CLIENT_ID'));
                    $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
                    $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
                    $client->setAccessType('offline');
                    $client->addScope(Google_Service_YouTube::YOUTUBE_READONLY);

                    if ($client->isAccessTokenExpired()) {
                        return redirect()->away($client->createAuthUrl());
                    }

                    $youtube = new Google_Service_YouTube($client);

                    $response = $youtube->videos->listVideos('snippet,contentDetails,statistics', [
                        'id' => $videoId,
                    ]);

                    if (count($response->items) > 0) {
                        $videoData = $response->items[0];

                        // Add YouTube data to the tutorial
                        $tutorial->thumbnail_url = $videoData->snippet->thumbnails->default->url;
                        $tutorial->views = $videoData->statistics->viewCount;
                        $tutorial->duration = $this->convertISO8601Duration($videoData->contentDetails->duration);
                    }
                } catch (\Exception $e) {
                    return response()->json(['error' => 'Failed to fetch video data.'], 500);
                }
            }
        }

        return response()->json($tutorial);
    }

    // Update an existing tutorial (Admin only)
    public function update(Request $request, $id)
    {
        // Validate input
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'video_url' => 'sometimes|required|url',
            'tags' => 'nullable|array',
        ]);

        // Find the tutorial
        $tutorial = Tutorial::findOrFail($id);

        // Update tags if provided
        if (isset($data['tags'])) {
            $data['tags'] = json_encode($data['tags']);
        }

        // Update the tutorial with new data
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

    // Helper function to extract the YouTube video ID from the URL
    private function extractVideoId($url)
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        return $query['v'] ?? null;
    }

    // Helper function to convert ISO 8601 duration (e.g., PT15M33S) into human-readable format
    private function convertISO8601Duration($duration)
    {
        $interval = new \DateInterval($duration);
        return $interval->format('%H:%I:%S');
    }
}
