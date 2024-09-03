<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
class UserController extends Controller
{
    public function show($username)
    {
        $user = User::where('username', $username)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($user);
    }
    public function update(Request $request, $username)
    {
        $user = User::where('username', $username)->firstOrFail();

        if ($user->id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'birthday' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other',
            'about' => 'nullable|string|max:1000',
            'profile_photo_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'birthday' => $request->birthday,
            'gender' => $request->gender,
            'about' => $request->about,
            'profile_photo_url' => $request->profile_photo_url
        ]);

        return response()->json(['message' => 'Profile updated successfully', 'user' => $user], 200);
    }


    public function uploadProfilePhoto(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $imageName = time() . '.' . $request->file->extension();
        $filePath = $request->file('file')->getPathname();
        $bucketName = env('AWS_BUCKET');
        $key = 'profile/' . $imageName;

        \Log::error('file: ' . $request->file('file')->getClientOriginalName());

        $s3 = new S3Client([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
            'http' => [
                'verify' => false,
                // 'verify' => storage_path('certs/pypad-instance-key.pem'),
                // we should look back in to this to make it true after creating ssl
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

            \Log::info('Successfully uploaded image to S3. URL: ' . $imageUrl);

            return response()->json([
                'message' => 'Image uploaded successfully',
                'url' => $imageUrl
            ], 200);

        } catch (AwsException $e) {
            \Log::error('S3 Upload Error: ' . $e->getMessage());

            return response()->json([
                'error' => 'An error occurred while uploading the image: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $perPage = 10;
        $friendsFirst = User::whereHas('friends', function ($query) {
            $query->where('status', 'accepted');
        })
        ->paginate($perPage, ['*'], 'friends_page');

        $nonFriends = User::whereDoesntHave('friends', function ($query) {
            $query->where('status', 'accepted');
        })
        ->paginate($perPage, ['*'], 'non_friends_page');

        return response()->json([
            'friends' => $friendsFirst->items(),
            'nonFriends' => $nonFriends->items(),
            'nextPageUrlFriends' => $friendsFirst->nextPageUrl(),
            'nextPageUrlNonFriends' => $nonFriends->nextPageUrl(),
        ]);
    }

    public function search(Request $request)
    {
        $searchQuery = $request->query('search');
        $perPage = 10;

        $friends = User::whereHas('friends', function ($query) {
            $query->where('status', 'accepted');
        })
        ->where('first_name', 'like', '%' . $searchQuery . '%')
        ->orWhere('last_name', 'like', '%' . $searchQuery . '%')
        ->orWhere('username', 'like', '%' . $searchQuery . '%')
        ->paginate($perPage, ['*'], 'friends_page');

        $nonFriends = User::whereDoesntHave('friends', function ($query) {
            $query->where('status', 'accepted');
        })
        ->where('first_name', 'like', '%' . $searchQuery . '%')
        ->orWhere('last_name', 'like', '%' . $searchQuery . '%')
        ->orWhere('username', 'like', '%' . $searchQuery . '%')
        ->paginate($perPage, ['*'], 'non_friends_page');

        return response()->json([
            'friends' => $friends->items(),
            'nonFriends' => $nonFriends->items(),
            'nextPageUrlFriends' => $friends->nextPageUrl(),
            'nextPageUrlNonFriends' => $nonFriends->nextPageUrl(),
        ]);
    }


}
