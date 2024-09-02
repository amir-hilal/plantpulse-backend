<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Friend;
use Illuminate\Support\Facades\Auth;

class FriendController extends Controller
{
    public function sendRequest(Request $request)
    {
        $request->validate([
            'friend_id' => 'required|exists:users,id',
        ]);

        $friend = Friend::create([
            'user_id' => Auth::id(),
            'friend_id' => $request->friend_id,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Friend request sent.', 'friend' => $friend], 201);
    }

    public function acceptRequest($id)
    {
        $friendRequest = Friend::where('friend_id', Auth::id())->where('id', $id)->firstOrFail();

        $friendRequest->update(['status' => 'accepted']);

        return response()->json(['message' => 'Friend request accepted.', 'friend' => $friendRequest], 200);
    }

    public function declineRequest($id)
    {
        $friendRequest = Friend::where('friend_id', Auth::id())->where('id', $id)->firstOrFail();

        $friendRequest->update(['status' => 'declined']);

        return response()->json(['message' => 'Friend request declined.', 'friend' => $friendRequest], 200);
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
}
