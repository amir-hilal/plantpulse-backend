<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
class ChatController extends Controller
{
    public function getMessages($receiver_id)
    {
        $sender_id = Auth::id(); // Get the logged-in user (sender)

        // Retrieve chat messages between the two users
        $messages = Message::where(function ($query) use ($sender_id, $receiver_id) {
            $query->where('sender_id', $sender_id)->where('receiver_id', $receiver_id);
        })
            ->orWhere(function ($query) use ($sender_id, $receiver_id) {
                $query->where('sender_id', $receiver_id)->where('receiver_id', $sender_id);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    // Send a message
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required',
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
        ]);

        return response()->json($message);
    }
}
