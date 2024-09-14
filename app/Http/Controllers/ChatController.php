<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation;

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

    public function getConversations()
    {
        $userId = Auth::id();

        $conversations = Conversation::where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
            ->get();

        return response()->json($conversations);
    }

    // Send a message
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required',
        ]);

        $senderId = Auth::id();
        $receiverId = $request->receiver_id;

        // Find or create the conversation between the sender and receiver
        $conversation = Conversation::findOrCreate($senderId, $receiverId);

        // Store the message
        $message = Message::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => $request->message,
            'conversation_id' => $conversation->id,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message);
    }
}
