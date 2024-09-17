<?php

namespace App\Http\Controllers;

use App\Models\CommunityPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use App\Models\User;
use App\Models\Friend;
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

        $post = CommunityPost::with(['user:id,first_name,last_name,profile_photo_url,username'])
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
                'author_username' => $post->user->username,
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

    public function fetchFriendsPosts(Request $request)
    {
        $userId = Auth::id();

        // Get all friend ids where the logged-in user is either user_id or friend_id, and the status is 'accepted'
        $friendIds = Friend::where('status', 'accepted')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('friend_id', $userId);
            })
            ->get()
            ->map(function ($friend) use ($userId) {
                // Get the friend ID (the other user in the relationship)
                return $friend->user_id === $userId ? $friend->friend_id : $friend->user_id;
            });

        $friendIds[] = $userId;

        // Fetch posts created by friends, including the author's information
        $posts = CommunityPost::whereIn('user_id', $friendIds)
            ->with(['user:id,first_name,last_name,profile_photo_url,username']) // Include author's details
            ->orderBy('created_at', 'desc')
            ->paginate(5); // Fetch 5 posts at a time for pagination

        // Transform the post data to include the required fields for the frontend
        $posts->getCollection()->transform(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'content' => $post->content,
                'image_url' => $post->image_url,
                'created_at' => $post->created_at,
                'author_name' => $post->user->first_name . ' ' . $post->user->last_name,
                'author_username' => $post->user->username,
                'author_profile_photo_url' => $post->user->profile_photo_url,
            ];
        });

        return response()->json($posts);
    }

    public function fetchPostsByUsername(Request $request, $username)
    {
        $user = User::where('username', $username)->firstOrFail();

        $posts = CommunityPost::where('user_id', $user->id)
            ->with(['user:id,first_name,last_name,profile_photo_url,username'])
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
                'author_username' => $post->user->username,
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

    public function deletePost($id)
{
    try {
        $post = CommunityPost::findOrFail($id);


        if (auth()->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully'], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Post not found or could not be deleted'], 404);
    }
}


}
