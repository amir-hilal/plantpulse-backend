<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friend;
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

        $loggedInUserId = auth()->id();
        $friendshipStatus = 'not_connected'; // Default status

        if ($loggedInUserId === $user->id) {
            $friendshipStatus = 'owner'; // The profile belongs to the logged-in user
        } else {
            $friendship = Friend::where(function ($query) use ($loggedInUserId, $user) {
                $query->where('user_id', $loggedInUserId)
                    ->where('friend_id', $user->id)
                    ->orWhere(function ($query) use ($loggedInUserId, $user) {
                        $query->where('friend_id', $loggedInUserId)
                            ->where('user_id', $user->id);
                    });
            })->first();

            if ($friendship) {
                if ($friendship->status === 'accepted') {
                    $friendshipStatus = 'connected';
                } elseif ($friendship->status === 'pending' && $friendship->user_id === $loggedInUserId) {
                    $friendshipStatus = 'request_sent';
                } elseif ($friendship->status === 'pending') {
                    $friendshipStatus = 'request_received';
                }
            }
        }

        $user->friendship_status = $friendshipStatus;

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
        $perPage = 20;
        $userId = auth()->id();

        // Fetch users excluding the current user
        $users = User::where('id', '<>', $userId)
            ->with([
                'friends' => function ($query) use ($userId) {
                    $query->where(function ($q) use ($userId) {
                        $q->where('friend_id', $userId)->orWhere('user_id', $userId);
                    });
                }
            ])
            ->paginate($perPage);

        $formattedUsers = $users->map(function ($user) use ($userId) {
            $friendship = $user->friends->first();
            $relationship_status = 'not_connected';

            if ($friendship) {
                if ($friendship->pivot->status === 'accepted') {
                    $relationship_status = 'connected';
                } elseif ($friendship->pivot->status === 'pending' && $friendship->user_id === $userId) {
                    $relationship_status = 'request_sent';
                } elseif ($friendship->pivot->status === 'pending') {
                    $relationship_status = 'request_received';
                }
            }

            return [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'profile_photo_url' => $user->profile_photo_url,
                'cover_photo_url' => $user->cover_photo_url,
                'about' => $user->about,
                'phone_number' => $user->phone_number,
                'gender' => $user->gender,
                'birthday' => $user->birthday,
                'address' => $user->address,
                'email_verified_at' => $user->email_verified_at,
                'relationship_status' => $relationship_status,
            ];
        });

        return response()->json([
            'users' => $formattedUsers,
            'nextPageUrl' => $users->nextPageUrl(),
        ]);
    }

    public function search(Request $request)
    {
        $searchQuery = $request->query('query');
        $perPage = 20;
        $userId = auth()->id();

        // Fetch users excluding the current user and applying the search filters
        $users = User::where('id', '<>', $userId)
            ->where(function ($query) use ($searchQuery) {
                $query->where('first_name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('last_name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('username', 'like', '%' . $searchQuery . '%');
            })
            ->with([
                'friends' => function ($query) use ($userId) {
                    $query->where(function ($q) use ($userId) {
                        $q->where('friend_id', $userId)->orWhere('user_id', $userId);
                    });
                }
            ])
            ->paginate($perPage);

        $formattedUsers = $users->map(function ($user) use ($userId) {
            $friendship = $user->friends->first();
            $relationship_status = 'not_connected';

            if ($friendship) {
                \Log::info('friendship_id' . $friendship);
                if ($friendship->pivot->status === 'accepted') {
                    $relationship_status = 'connected';
                } elseif ($friendship->pivot->status === 'pending' && $friendship->user_id === $userId) {
                    $relationship_status = 'request_sent';
                } elseif ($friendship->pivot->status === 'pending') {
                    $relationship_status = 'request_received';
                }
            }

            return [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'profile_photo_url' => $user->profile_photo_url,
                'cover_photo_url' => $user->cover_photo_url,
                'about' => $user->about,
                'phone_number' => $user->phone_number,
                'gender' => $user->gender,
                'birthday' => $user->birthday,
                'address' => $user->address,
                'email_verified_at' => $user->email_verified_at,
                'relationship_status' => $relationship_status,
            ];
        });

        return response()->json([
            'users' => $formattedUsers,
            'nextPageUrl' => $users->nextPageUrl(),
        ]);
    }



}
