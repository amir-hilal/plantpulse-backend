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

        // Fetch conversations where the user is involved
        $conversations = Conversation::where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
            ->with(['lastMessage', 'userOne:id,first_name,last_name,username,email,profile_photo_url', 'userTwo:id,first_name,last_name,username,email,profile_photo_url'])
            ->get();

        // Map the conversations to include the relevant user information
        $conversations = $conversations->map(function ($conversation) use ($userId) {
            $receiver = $conversation->user_one_id === $userId
                ? $conversation->userTwo
                : $conversation->userOne;

            return [
                'id' => $conversation->id,
                'last_message' => $conversation->lastMessage,
                'receiver' => [
                    'id' => $receiver->id,
                    'first_name' => $receiver->first_name,
                    'last_name' => $receiver->last_name,
                    'username' => $receiver->username,
                    'email' => $receiver->email,
                    'profile_photo_url' => $receiver->profile_photo_url,
                ]
            ];
        });

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
