<?php

namespace App\Http\Controllers;

use App\Models\CommunityPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use App\Models\User;
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

        $post = CommunityPost::with(['user:id,first_name,last_name,profile_photo_url'])
            ->find($post->id);

        return response()->json([
            'message' => 'Post created successfully',
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'content' => $post->content,
                'image_url' => $post->image_url,
                'created_at' => $post->created_at,
                'author_name' => $post->user->first_name . ' ' . $post->user->last_name,
                'author_profile_photo_url' => $post->user->profile_photo_url,
            ]
        ], 201);
    }

    public function fetchAllPosts(Request $request)
    {
        $posts = CommunityPost::orderBy('created_at', 'desc')
            ->paginate(5); // Fetch 5 posts at a time

        return response()->json($posts);
    }

    public function fetchPostsByUsername(Request $request, $username)
    {
        $user = User::where('username', $username)->firstOrFail();

        $posts = CommunityPost::where('user_id', $user->id)
            ->with(['user:id,first_name,last_name,profile_photo_url'])
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        $posts->transform(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'content' => $post->content,
                'image_url' => $post->image_url,
                'created_at' => $post->created_at,
                'author_name' => $post->user->first_name . ' ' . $post->user->last_name,
                'author_profile_photo_url' => $post->user->profile_photo_url,
            ];
        });

        return response()->json($posts);

    }

    public function fetchPostById($id)
    {
        $post = CommunityPost::with('user')->find($id);

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        return response()->json($post);
    }

}
