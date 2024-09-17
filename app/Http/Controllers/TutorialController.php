<?php

namespace App\Http\Controllers;

use App\Models\Tutorial;
use Google_Client;
use Google_Service_YouTube;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class TutorialController extends Controller
{
    // Fetch all tutorials with YouTube data (paginated)
    public function index(Request $request)
    {
        $tutorials = Tutorial::paginate(10);
        $this->fetchYouTubeData($tutorials);
        return response()->json($tutorials);
    }

    // Search for tutorials with a query term and return paginated response
    public function search(Request $request)
    {
        $query = $request->input('q'); // Get the search query

        // Filter tutorials by title or description
        $tutorials = Tutorial::where('title', 'LIKE', '%' . $query . '%')
            ->orWhere('description', 'LIKE', '%' . $query . '%')
            ->orWhere('tags', 'LIKE', '%' . $query . '%')
            ->paginate(10);

        $this->fetchYouTubeData($tutorials);
        return response()->json($tutorials);
    }

    // Fetch a specific tutorial by ID
    public function show($id)
    {
        $tutorial = Tutorial::findOrFail($id);
        $this->fetchYouTubeData(collect([$tutorial])); // Passing as collection to reuse the method
        return response()->json($tutorial);
    }

    // Store a new tutorial (Admin only)
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'video_url' => 'required|url',
            'tags' => 'nullable|array',
        ]);

        $tutorial = Tutorial::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'video_url' => $data['video_url'],
            'tags' => json_encode($data['tags']),
        ]);

        return response()->json($tutorial, 201);
    }

    // Update an existing tutorial (Admin only)
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'video_url' => 'sometimes|required|url',
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

    // Fetch YouTube data for all tutorials in a collection
    private function fetchYouTubeData($tutorials)
    {
        $client = new Google_Client();
        $guzzleClient = new Client(['verify' => false]);
        $client->setHttpClient($guzzleClient);
        $client->setAuthConfig(storage_path(env('GOOGLE_SERVICE_ACCOUNT_JSON')));
        $client->setScopes([Google_Service_YouTube::YOUTUBE_READONLY]);

        $youtube = new Google_Service_YouTube($client);

        foreach ($tutorials as $tutorial) {
            if ($tutorial->video_url) {
                $videoId = $this->extractVideoId($tutorial->video_url);

                if ($videoId) {
                    try {
                        $response = $youtube->videos->listVideos('snippet,contentDetails,statistics', [
                            'id' => $videoId,
                        ]);

                        if (count($response->items) > 0) {
                            $videoData = $response->items[0];
                            $this->setYouTubeData($tutorial, $videoData);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Failed to fetch YouTube video data: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    // Set YouTube data (thumbnail, views, duration) for a tutorial
    private function setYouTubeData($tutorial, $videoData)
    {
        // Select the best available thumbnail
        if (isset($videoData->snippet->thumbnails->maxres)) {
            $tutorial->thumbnail_url = $videoData->snippet->thumbnails->maxres->url;
        } elseif (isset($videoData->snippet->thumbnails->standard)) {
            $tutorial->thumbnail_url = $videoData->snippet->thumbnails->standard->url;
        } elseif (isset($videoData->snippet->thumbnails->high)) {
            $tutorial->thumbnail_url = $videoData->snippet->thumbnails->high->url;
        } elseif (isset($videoData->snippet->thumbnails->medium)) {
            $tutorial->thumbnail_url = $videoData->snippet->thumbnails->medium->url;
        } else {
            $tutorial->thumbnail_url = $videoData->snippet->thumbnails->default->url;
        }

        // Set views and duration
        $tutorial->views = $videoData->statistics->viewCount;
        $tutorial->duration = $this->convertISO8601Duration($videoData->contentDetails->duration);
    }

    // Extract the YouTube video ID from the URL
    private function extractVideoId($url)
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        return $query['v'] ?? null;
    }

    // Convert ISO 8601 duration to human-readable format
    private function convertISO8601Duration($duration)
    {
        $interval = new \DateInterval($duration);
        return $interval->format('%H:%I:%S');
    }
}
