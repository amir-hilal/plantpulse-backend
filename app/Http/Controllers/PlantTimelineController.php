<?php
namespace App\Http\Controllers;

use App\Models\PlantTimeline;
use Illuminate\Http\Request;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Http;
use Aws\Exception\AwsException;
use App\Models\Plant;
use Carbon\Carbon;

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
        \Log::info('Store method called.');

        $validated = $request->validate([
            'plant_id' => 'required|exists:plants,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        \Log::info('Validation passed.', ['plant_id' => $validated['plant_id']]);

        $plant = Plant::find($validated['plant_id']);
        \Log::info('Fetched plant info.', ['plant' => $plant]);

        $previousTimelines = PlantTimeline::where('plant_id', $validated['plant_id'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->pluck('description')
            ->toArray();

        \Log::info('Fetched previous 5 timeline entries.', ['count' => count($previousTimelines)]);

        $weather = $this->fetchCurrentWeather($request);
        \Log::info('Fetched current weather.', ['weather' => $weather]);

        $weeklyWateringSchedule = $this->getPlantWeekWateringSchedule($plant);
        \Log::info('Fetched weekly watering schedule.', ['watering_schedule' => $weeklyWateringSchedule]);

        $diseasePrediction = null;
        if ($request->hasFile('image')) {
            \Log::info('Image file detected, sending to disease detection.');
            $diseasePrediction = $this->detectPlantDisease($request->file('image'));
            if ($diseasePrediction) {
                \Log::info('Disease prediction received.', $diseasePrediction);
            } else {
                \Log::warning('Disease prediction failed.');
            }
        }

        $aiMessages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant for plant care.'],
        ];

        foreach (array_reverse($previousTimelines) as $timelineDescription) {
            $aiMessages[] = ['role' => 'user', 'content' => $timelineDescription];
        }

        if ($diseasePrediction) {
            $predictionText = "Prediction: " . $diseasePrediction['predicted_class'] .
                " (Confidence: " . round($diseasePrediction['confidence'] * 100, 2) . "%)";
            $aiMessages[] = ['role' => 'user', 'content' => $predictionText];
        }

        $plantInfoText = "Plant Information: Name - " . $plant->name . ", Type - " . $plant->type . ", Planted on - " . $plant->planted_on;
        $aiMessages[] = ['role' => 'user', 'content' => $plantInfoText];

        $weatherText = "Current weather: Temperature - " . $weather['main']['temp'] . "°C, Humidity - " . $weather['main']['humidity'] . "%";
        $aiMessages[] = ['role' => 'user', 'content' => $weatherText];

        $wateringScheduleText = "Weekly Watering Schedule: " . json_encode($weeklyWateringSchedule);
        $aiMessages[] = ['role' => 'user', 'content' => $wateringScheduleText];

        $aiMessages[] = ['role' => 'user', 'content' => $validated['description']];

        $prompt = "The prediction object is a response from a trained AI model that predicts the condition of a plant's leaf. ";
        $prompt .= "Currently, we only trained the model on tomato, potato, and pepper bell leaves, so if the category or the name ";
        $prompt .= "of the plant does not relate the leaf's name, just focus on the rest of the info, because you know everything about all plants, the prediction object is just a little help for you for better response, and give a helpfull message even if it doesn't make sense to you, and start your resonse with something helpful about my plant type and it's watering events and stuff you know about the plant, give tips and ticks";

        \Log::info('Sending AI request to assistant.', ['messages' => $aiMessages]);

        $aiResponse = $this->getAssistantResponse($aiMessages, $prompt);

        if ($aiResponse) {
            \Log::info('AI response received.', ['aiResponse' => $aiResponse]);
        } else {
            \Log::warning('No response from AI service.');
        }

        $timeline = PlantTimeline::create([
            'plant_id' => $validated['plant_id'],
            'description' => $validated['description'],
            'image_path' => null, // This will be set after uploading to S3
            'source' => 'user',
        ]);

        \Log::info('User timeline created.', ['timeline_id' => $timeline->id]);
        sleep(1);
        if ($aiResponse) {
            PlantTimeline::create([
                'plant_id' => $validated['plant_id'],
                'description' => $aiResponse,
                'source' => 'ai',
            ]);
            \Log::info('AI timeline entry created.');
        }

        if ($request->hasFile('image')) {
            \Log::info('Uploading image to S3.');
            $imagePath = $this->uploadImageToS3($request->file('image'));
            if ($imagePath) {
                $timeline->update(['image_path' => $imagePath]);
                \Log::info('Image uploaded to S3 and timeline updated.', ['image_path' => $imagePath]);
            } else {
                \Log::error('Image upload to S3 failed.');
            }
        }

        return response()->json(['aiResponse' => $aiResponse, 'userTimeline' => $timeline], 201);
    }

    private function detectPlantDisease($file)
    {
        try {
            \Log::info('Sending image to disease prediction service.');

            $response = Http::attach('file', fopen($file->getPathname(), 'r'), $file->getClientOriginalName())
                ->post('http://3.29.238.173:8000/predict/');

            if ($response->successful()) {
                \Log::info('Disease prediction successful.', ['response' => $response->json()]);
                return $response->json(); // Expecting 'predicted_class', 'confidence'
            } else {
                throw new \Exception('Disease detection API returned an error.');
            }
        } catch (\Exception $e) {
            \Log::error('Plant Disease Detection Error: ' . $e->getMessage());
            return null;
        }
    }

    private function fetchCurrentWeather($request)
    {
        $lat = $request->input('lat');
        $lon = $request->input('lon');
        $apiKey = env('OPENWEATHER_API_KEY');
        $weatherUrl = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";

        try {
            $response = Http::withOptions([
                'verify' => false
            ])->get($weatherUrl);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Failed to fetch weather data.');
        } catch (\Exception $e) {
            \Log::error('Weather API Error: ' . $e->getMessage());
            return ['main' => ['temp' => 'Unknown', 'humidity' => 'Unknown']];
        }
    }

    private function getPlantWeekWateringSchedule($plant)
    {
        $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
        $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');

        $wateringEvents = $plant->wateringEvents()
            ->whereBetween('scheduled_date', [$startOfWeek, $endOfWeek])
            ->get()
            ->map(function ($event) {
                return [
                    'scheduled_date' => $event->scheduled_date,
                    'is_done' => $event->is_done,
                    'completed_at' => $event->completed_at,
                ];
            });

        return $wateringEvents;
    }

    private function getAssistantResponse($messages, $prompt)
    {
        try {
            \Log::info('Sending request to AI service.');

            array_unshift($messages, ['role' => 'system', 'content' => $prompt]);

            $response = Http::withoutVerifying()->post('https://openai-service.vercel.app/api/openai/chat', [
                'messages' => $messages,
            ]);

            $assistantMessage = $response->json()['choices'][0]['message']['content'] ?? null;
            \Log::info('Received response from AI service.', ['response' => $assistantMessage]);
            return $assistantMessage;
        } catch (\Exception $e) {
            \Log::error('AI Service Error: ' . $e->getMessage());
            return null;
        }
    }

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
            \Log::info('Uploading file to S3.', ['file' => $imageName]);

            $result = $s3->putObject([
                'Bucket' => $bucketName,
                'Key' => $key,
                'SourceFile' => $file->getPathname(),
                'ACL' => 'public-read',
            ]);

            \Log::info('S3 Upload Success', ['file' => $imageName, 'url' => $result['ObjectURL']]);

            return $result['ObjectURL'];
        } catch (AwsException $e) {
            \Log::error('S3 Upload Error: ' . $e->getMessage());
            return null;
        }
    }

}
