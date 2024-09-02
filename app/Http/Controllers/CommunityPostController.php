<?php

namespace App\Http\Controllers;

use App\Models\CommunityPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class CommunityPostController extends Controller
{
    public function createPost(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $imageUrl = null;

        if ($request->hasFile('file')) {
            $imageName = time() . '.' . $request->file->extension();
            $filePath = $request->file('file')->getPathname();
            $bucketName = env('AWS_BUCKET');
            $key = 'posts/' . $imageName;

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

                $imageUrl = $result['ObjectURL'];

            } catch (AwsException $e) {
                return response()->json(['error' => 'Failed to upload image to S3: ' . $e->getMessage()], 500);
            }
        }

        $post = CommunityPost::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'content' => $request->content,
            'image_url' => $imageUrl,
        ]);

        return response()->json([
            'message' => 'Post created successfully',
            'post' => $post
        ], 201);
    }
}
