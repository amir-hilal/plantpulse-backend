<?php
namespace App\Http\Controllers;

use App\Models\Garden;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class GardenController extends Controller
{
    // Get all gardens for the authenticated user
    public function index()
    {
        $gardens = Garden::where('user_id', Auth::id())->get();
        return response()->json($gardens);
    }

    // Create a new garden with image upload to S3
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            // 'file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate image
        ]);

        // Handle image upload
        // $imageUrl = null;
        // if ($request->hasFile('file')) {
        //     $imageUrl = $this->uploadImageToS3($request->file('file'));
        // }

        $garden = Garden::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'location' => $request->location,
            // 'image_url' => $imageUrl,
        ]);

        return response()->json($garden, 201);
    }

    // Show a specific garden
    public function show($id)
    {
        $garden = Garden::where('user_id', Auth::id())->findOrFail($id);
        return response()->json($garden);
    }

    // Update a garden with image upload to S3
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            // 'file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate image
        ]);

        $garden = Garden::where('user_id', Auth::id())->findOrFail($id);

        // Handle image upload
        // if ($request->hasFile('file')) {
        //     $imageUrl = $this->uploadImageToS3($request->file('file'));
        //     $garden->update(['image_url' => $imageUrl]);
        // }

        $garden->update($request->only('name', 'location'));

        return response()->json($garden);
    }

    // Delete a garden
    public function destroy($id)
    {
        $garden = Garden::where('user_id', Auth::id())->findOrFail($id);
        $garden->delete();

        return response()->json(['message' => 'Garden have been deleted successfully.'], 200);
    }

    public function updateImage(Request $request, $id)
    {
        $request->validate(['file' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048']);

        $garden = Garden::where('user_id', Auth::id())->findOrFail($id);

        // Handle image upload
        if ($request->hasFile('file')) {
            $imageUrl = $this->uploadImageToS3($request->file('file'));
            $garden->update(['image_url' => $imageUrl]);
        }
        return response()->json($garden);
    }

    // Upload image to S3
    private function uploadImageToS3($file)
    {
        $imageName = time() . '.' . $file->extension();
        $filePath = $file->getPathname();
        $bucketName = env('AWS_BUCKET');
        $key = 'garden-images/' . $imageName;

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
            \Log::error('S3 Upload Error: ' . $e->getMessage());
            return null;
        }
    }
}
