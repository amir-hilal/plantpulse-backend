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

        $plantedDate = Carbon::now()->subDays($validated['age']);
        unset($validated['age']);

        $imagePath = '';
        if ($request->hasFile('file')) {
            $imagePath = $this->uploadImageToS3($request->file('file'));
        }

        $plant = Plant::create(array_merge($validated, [
            'planted_date' => $plantedDate,
            'image_url' => $imagePath,
        ]));

        PlantTimeline::create([
            'plant_id' => $plant->id,
            'description' => $request->description,
            'image_path' => $imagePath,
        ]);

        $plant->scheduleWateringEvents();

        $nextWateringEvent = $plant->wateringEvents()->orderBy('scheduled_date', 'asc')->first();
        if ($nextWateringEvent) {
            $plant->update([
                'next_time_to_water' => $nextWateringEvent->scheduled_date
            ]);
        }

        $plant->age_in_days = $plant->getAgeInDaysAttribute();
        $plant->formatted_age = $plant->getFormattedAgeAttribute();
        return response()->json($plant, 201);
    }


    // Show a single plant
    public function show($id)
    {
        // Retrieve the plant with the garden relationship
        $plant = Plant::with('garden:id,name')->findOrFail($id);

        // Calculate age and formatted age
        $plant->age_in_days = $plant->getAgeInDaysAttribute();
        $plant->formatted_age = $plant->getFormattedAgeAttribute();

        // Return plant data along with garden name and formatted age
        return response()->json([
            'plant' => $plant,
            'garden_name' => $plant->garden->name, // Include garden's name in the response
        ]);
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
            'file' => 'nullable|image|mimes:jpg,jpeg,png|max:2048' // Validate image file
        ]);
        // Check if an image file was uploaded
        if ($request->hasFile('image')) {
            $imageUrl = $this->uploadImageToS3($request->file('image'));

            if ($imageUrl) {
                $validated['image_url'] = $imageUrl; // Set the new image URL if upload was successful
            }
        }

        // Update the plant with the validated data
        $plant->update($validated);

        // Optionally, update the plant's timeline with the new description
        if (!empty($validated['description'])) {
            PlantTimeline::create([
                'plant_id' => $plant->id,
                'description' => $validated['description'],
                'image_path' => $validated['image_url'] ?? $plant->image_url, // Use the new image if available, otherwise use the existing one
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
