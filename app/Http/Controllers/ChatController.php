<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation;

class ChatController extends Controller
{
    public function getMessages($receiver_id, Request $request)
    {
        $sender_id = Auth::id();
        $perPage = $request->query('per_page', 10);

        $messages = Message::where(function ($query) use ($sender_id, $receiver_id) {
            $query->where('sender_id', $sender_id)->where('receiver_id', $receiver_id);
        })
            ->orWhere(function ($query) use ($sender_id, $receiver_id) {
                $query->where('sender_id', $receiver_id)->where('receiver_id', $sender_id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($messages);
    }

    public function getConversations()
    {
        $userId = Auth::id();

        $conversations = Conversation::where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
            ->with('lastMessage')
            ->get();

        return response()->json($conversations);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required',
        ]);

        $senderId = Auth::id();
        $receiverId = $request->receiver_id;

        $conversation = Conversation::findOrCreate($senderId, $receiverId);
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
