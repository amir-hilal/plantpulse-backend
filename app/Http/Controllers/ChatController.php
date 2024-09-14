<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\MessageSent;

class ChatController extends Controller
{
    public function sendMessage(Request $request)
    {
        $message = $request->input('message');
        broadcast(new MessageSent($message));
        return response()->json(['status' => 'Message sent!']);
    }
}
