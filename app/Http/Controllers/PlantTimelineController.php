<?php
namespace App\Http\Controllers;

use App\Models\PlantTimeline;
use Illuminate\Http\Request;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Http; // To send requests to the AI service
use Aws\Exception\AwsException;

class PlantTimelineController extends Controller
{
    public function index($plantId, Request $request)
    {
        $perPage = $request->get('perPage', 10);
        $page = $request->get('page', 1);

        $timelines = PlantTimeline::where('plant_id', $plantId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($timelines);
    }

    public function store(Request $request)
    {
        // Validate the input
        $validated = $request->validate([
            'plant_id' => 'required|exists:plants,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        // Store the user's input
        $timeline = PlantTimeline::create([
            'plant_id' => $validated['plant_id'],
            'description' => $validated['description'],
            'image_path' => $request->hasFile('image') ? $this->uploadImageToS3($request->file('image')) : null, // Check if the image exists
            'source' => 'user', // Mark it as user's input
        ]);

        // Retrieve the previous 5 timeline entries for this plant (excluding the current one)
        // $previousTimelines = PlantTimeline::where('plant_id', $validated['plant_id'])
        //     ->orderBy('created_at', 'desc')
        //     ->take(5)
        //     ->pluck('description')
        //     ->toArray();

        // Prepare AI request
        // $aiMessages = [
        //     ['role' => 'system', 'content' => 'You are a helpful assistant for plant care.']
        // ];

        // foreach (array_reverse($previousTimelines) as $timelineDescription) {
        //     $aiMessages[] = ['role' => 'user', 'content' => $timelineDescription];
        // }

        // $aiMessages[] = ['role' => 'user', 'content' => $validated['description']];

        // Log AI request
        // \Log::info('AI Request', ['messages' => $aiMessages]);

        // Send the message to the assistant and store the response
        // $aiResponse = $this->getAssistantResponse($aiMessages);

        // Log AI response
        // \Log::info('AI Response', ['response' => $aiResponse]);

        // if ($aiResponse) {
        //     PlantTimeline::create([
        //         'plant_id' => $validated['plant_id'],
        //         'description' => $aiResponse,
        //         'source' => 'assistant', // Mark it as assistant's input
        //     ]);
        // }

        // Disease detection using the Kaggle model
        if ($request->hasFile('image')) {
            $diseasePrediction = $this->detectPlantDisease($timeline->image_path);
            if ($diseasePrediction) {
                PlantTimeline::create([
                    'plant_id' => $validated['plant_id'],
                    'description' => "Detected disease: " . $diseasePrediction,
                    'source' => 'ai', // Mark it as AI's input
                ]);
            }
        }

        return response()->json(['userTimeline' => $timeline], 201);
    }

    // Function to detect plant disease using the Kaggle model
    private function detectPlantDisease($imageUrl)
    {
        try {
            $response = Http::withoutVerifying()->post('https://your-service-to-detect-plant-disease', [
                'image_url' => $imageUrl,
            ]);

            // Assume the response contains a field 'disease' with the detected disease name
            return $response->json()['disease'];
        } catch (\Exception $e) {
            \Log::error('Plant Disease Detection Error: ' . $e->getMessage());
            return null;
        }
    }
    // Upload image to S3
    private function uploadImageToS3($file)
    {
        $imageName = time() . '.' . $file->extension();
        $bucketName = env('AWS_BUCKET');
        $key = 'timeline-images/' . $imageName;

        $s3 = new S3Client([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
            'http' => [
                'verify' => false, // Disable SSL verification
            ],
        ]);

        try {
            $result = $s3->putObject([
                'Bucket' => $bucketName,
                'Key' => $key,
                'SourceFile' => $file->getPathname(),
                'ACL' => 'public-read',
            ]);

            // Log successful upload
            \Log::info('S3 Upload Success', ['file' => $imageName, 'url' => $result['ObjectURL']]);

            return $result['ObjectURL'];
        } catch (AwsException $e) {
            \Log::error('S3 Upload Error: ' . $e->getMessage());
            return null;
        }
    }

    // Function to send the request to AI service
    // private function getAssistantResponse($messages)
    // {
    //     try {
    //         $response = Http::withoutVerifying()->post('https://openai-service.vercel.app/api/openai/chat', [
    //             'messages' => $messages,
    //         ]);

    //         $assistantMessage = $response->json()['choices'][0]['message']['content'];
    //         return $assistantMessage;
    //     } catch (\Exception $e) {
    //         \Log::error('AI Service Error: ' . $e->getMessage());
    //         return null;
    //     }
    // }

}
