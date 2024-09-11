<?php
namespace App\Http\Controllers;

use App\Models\Plant;
use App\Models\PlantTimeline;
use Illuminate\Http\Request;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;
use Aws\Exception\AwsException;

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
            'description'=> 'nullable|string',
            'file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);
        $imagePath = '';
        if ($request->hasFile('file')) {
            $imagePath = $this->uploadImageToS3($request->file('file'));
        }


        $plant = Plant::create(array_merge($validated, ['image_url' => $imagePath]));
        PlantTimeline::create([
            'plant_id' => $plant->id,
            'description' => $request->description,
            'image_path' => $imagePath, // Store the image path in the timeline if available
        ]);

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
