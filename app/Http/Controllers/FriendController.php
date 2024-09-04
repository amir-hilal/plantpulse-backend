<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class FriendController extends Controller
{
    public function sendRequest(Request $request)
    {
        $request->validate([
            'friend_id' => 'required|exists:users,id',
        ]);

        $userId = Auth::id();
        $friendId = $request->friend_id;

        // Check if a request exists in both directions
        $existingRequest = Friend::withTrashed()
            ->where(function ($query) use ($userId, $friendId) {
                $query->where('user_id', $userId)
                    ->where('friend_id', $friendId);
            })
            ->orWhere(function ($query) use ($userId, $friendId) {
                $query->where('user_id', $friendId)
                    ->where('friend_id', $userId);
            })
            ->first();

        // Handle the case where a reverse request is allowed (only if the original was declined)
        if ($existingRequest && $existingRequest->status === 'declined') {
            // Allow the reverse request only if it was declined
            if ($existingRequest->user_id === $friendId && $existingRequest->friend_id === $userId) {
                // Create a new request in the reverse direction
                $friend = Friend::create([
                    'user_id' => $userId,
                    'friend_id' => $friendId,
                    'status' => 'pending',
                ]);

                return response()->json(['message' => 'Friend request sent.', 'friend' => $friend], 201);
            } else {
                return response()->json(['message' => 'Friend request already declined. No further requests allowed.'], 400);
            }
        }

        // Prevent sending a new request if an existing one is pending or accepted
        if ($existingRequest && $existingRequest->status === 'pending') {
            return response()->json(['message' => 'Friend request already sent and pending.'], 400);
        }

        if ($existingRequest && $existingRequest->status === 'accepted') {
            return response()->json(['message' => 'Friend request already accepted.'], 400);
        }

        $friend = Friend::create([
            'user_id' => Auth::id(),
            'friend_id' => $request->friend_id,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Friend request sent.', 'friend' => $friend], 201);
    }

    public function acceptRequest($id)
    {
        // Find the friend request where the logged-in user is the friend
        $friendRequest = Friend::where('friend_id', Auth::id())
            ->where('user_id', $id)
            ->with('user:id,email,first_name,last_name,username,profile_photo_url,cover_photo_url,about,phone_number,gender,birthday,address,email_verified_at')
            ->first();

        if (!$friendRequest) {
            return response()->json(['message' => 'Friend request not found or already accepted.'], 404);
        }

        // Check if the request is pending
        if ($friendRequest->status === 'pending') {
            $friendRequest->update(['status' => 'accepted']);

            return response()->json([
                'message' => 'Friend request accepted.',
                'friend' => $friendRequest,
            ], 200);
        }

        return response()->json(['message' => 'Friend request is not pending.'], 400);
    }


    public function declineRequest($id)
    {
        $friendRequest = Friend::where('friend_id', Auth::id())->where('user_id', $id)->first();

        if (!$friendRequest) {
            return response()->json(['message' => 'Friend request not found or already declined.'], 404);
        }

        if ($friendRequest->status === 'pending') {
            $friendRequest->update(['status' => 'declined']);
            $friendRequest->delete();
            return response()->json(['message' => 'Friend request declined and removed.', 'friend' => $friendRequest], 200);
        }

        return response()->json(['message' => 'Friend request is not pending.'], 400);
    }



    public function removeFriend($id)
    {
        $friend = Friend::where(function ($query) use ($id) {
            $query->where('user_id', Auth::id())
                ->orWhere('friend_id', Auth::id());
        })->where('id', $id)->firstOrFail();

        $friend->delete();

        return response()->json(['message' => 'Friend removed.', 'friend' => $friend], 200);
    }

    public function listFriends()
    {
        $userId = Auth::id();

        // Get the accepted friends where the logged-in user is either user_id or friend_id
        $friends = Friend::where('status', 'accepted')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('friend_id', $userId);
            })
            ->with(['user', 'friend'])
            ->get();

        $formattedFriends = $friends->map(function ($friend) use ($userId) {
            // If the logged-in user is user_id, return the friend_id details, otherwise return user_id details
            $friendData = $friend->user_id === $userId ? $friend->friend : $friend->user;

            return [
                'id' => $friendData->id,
                'email' => $friendData->email,
                'first_name' => $friendData->first_name,
                'last_name' => $friendData->last_name,
                'username' => $friendData->username,
                'profile_photo_url' => $friendData->profile_photo_url,
                'cover_photo_url' => $friendData->cover_photo_url,
                'about' => $friendData->about,
                'phone_number' => $friendData->phone_number,
                'gender' => $friendData->gender,
                'birthday' => $friendData->birthday,
                'address' => $friendData->address,
                'email_verified_at' => $friendData->email_verified_at,
                'relationship_status' => 'connected'
            ];
        });

        return response()->json(['friends' => $formattedFriends], 200);
    }

    public function listRequests()
    {
        $requests = Friend::where('friend_id', Auth::id())
            ->where('status', 'pending')
            ->with('user')
            ->get();

        return response()->json(['requests' => $requests], 200);
    }
}
