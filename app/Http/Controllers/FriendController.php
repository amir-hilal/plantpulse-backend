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
}
