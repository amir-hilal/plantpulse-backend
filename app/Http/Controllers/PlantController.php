<?php
namespace App\Http\Controllers;

use App\Models\Plant;
use App\Models\PlantTimeline;
use Illuminate\Http\Request;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;
use Aws\Exception\AwsException;
use Carbon\Carbon;

class PlantController extends Controller
{
    // Get all plants for a specific garden
    public function index($gardenId)
    {
        $plants = Plant::where('garden_id', $gardenId)->get();

        $plants->transform(function ($plant) {

            $plant->age_in_days = $plant->getAgeInDaysAttribute();
            $plant->formatted_age = $plant->getFormattedAgeAttribute();
            return $plant;
        });

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
            'description' => 'nullable|string',
            'file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'watering_frequency' => 'required|integer|min:1|max:7',
        ]);

        // Calculate planted date based on the provided age (in days)
        $plantedDate = Carbon::now()->subDays($validated['age']);
        unset($validated['age']);

        $imagePath = '';
        if ($request->hasFile('file')) {
            $imagePath = $this->uploadImageToS3($request->file('file'));
        }

        // Store plant with planted_date
        $plant = Plant::create(array_merge($validated, [
            'planted_date' => $plantedDate,
            'image_url' => $imagePath,
        ]));

        // Create a timeline entry
        PlantTimeline::create([
            'plant_id' => $plant->id,
            'description' => $request->description,
            'image_path' => $imagePath,
        ]);

        $plant->age_in_days = $plant->getAgeInDaysAttribute();
        $plant->formatted_age = $plant->getFormattedAgeAttribute();
        $plant->scheduleWateringEvents(); 
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

        // Validate the request, remove age and replace important_note with description
        $validated = $request->validate([
            'name' => 'string',
            'category' => 'string',
            'description' => 'nullable|string', // Replace important_note with description
            'last_watered' => 'nullable|date',
            'next_time_to_water' => 'nullable|date',
            'height' => 'nullable|numeric',
            'health_status' => 'string',
            'watering_frequency' => 'nullable|integer|min:1|max:7',

        ]);

        // Update the plant with the validated data
        $plant->update($validated);

        // Optionally, update the plant's timeline with the new description
        if (!empty($validated['description'])) {
            PlantTimeline::create([
                'plant_id' => $plant->id,
                'description' => $validated['description'],
                'image_path' => $plant->image_url,
            ]);
        }

        return response()->json($plant);
    }

    // Delete a plant
    public function destroy($id)
    {
        $plant = Plant::findOrFail($id);
        $plant->delete();
        return response()->json(['message' => 'Plant deleted successfully']);
    }

    private function uploadImageToS3($file)
    {
        $imageName = time() . '.' . $file->extension();
        $filePath = $file->getPathname();
        $bucketName = env('AWS_BUCKET');
        $key = 'plant-images/' . $imageName;

        $s3 = new S3Client([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
            'http' => [
                'verify' => false,
            ],
        ]);

        try {
            $result = $s3->putObject([
                'Bucket' => $bucketName,
                'Key' => $key,
                'SourceFile' => $filePath,
                'ACL' => 'public-read',
            ]);

            return $result['ObjectURL'];
        } catch (AwsException $e) {
            Log::error('S3 Upload Error: ' . $e->getMessage());
            return null;
        }
    }
}
