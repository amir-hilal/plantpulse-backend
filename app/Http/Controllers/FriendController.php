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

        $existingRequest = Friend::withTrashed()
            ->where('user_id', Auth::id())
            ->where('friend_id', $request->friend_id)
            ->first();

        if ($existingRequest && $existingRequest->status === 'pending') {
            return response()->json(['message' => 'Friend request already sent and pending.'], 400);
        }

        if ($existingRequest && $existingRequest->trashed()) {
            // If a request was soft-deleted, we can "resurrect" it instead of creating a new one
            $existingRequest->restore();
            $existingRequest->update(['status' => 'pending']);

            return response()->json(['message' => 'Friend request re-sent.', 'friend' => $existingRequest], 201);
        }

        // If no existing request, create a new one
        $friend = Friend::create([
            'user_id' => Auth::id(),
            'friend_id' => $request->friend_id,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Friend request sent.', 'friend' => $friend], 201);
    }

    public function acceptRequest($id)
    {
        $friendRequest = Friend::where('friend_id', Auth::id())->where('user_id', $id)->first();

        if (!$friendRequest) {
            return response()->json(['message' => 'Friend request not found or already accepted.'], 404);
        }

        if ($friendRequest->status === 'pending') {
            $friendRequest->update(['status' => 'accepted']);
            return response()->json(['message' => 'Friend request accepted.', 'friend' => $friendRequest], 200);
        }

        return response()->json(['message' => 'Friend request is not pending.'], 400);
    }

    public function declineRequest($id)
    {
        $friendRequest = Friend::where('friend_id', Auth::id())->where('id', $id)->first();

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
        $friends = Friend::where('status', 'accepted')
            ->where(function ($query) {
                $query->where('user_id', Auth::id())
                    ->orWhere('friend_id', Auth::id());
            })
            ->with(['user', 'friend'])
            ->get();

        return response()->json(['friends' => $friends], 200);
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
