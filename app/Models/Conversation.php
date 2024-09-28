<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['user_one_id', 'user_two_id'];

    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public static function findOrCreate($userOneId, $userTwoId)
    {
        // First, try to find the conversation
        $conversation = self::where(function ($query) use ($userOneId, $userTwoId) {
            $query->where('user_one_id', $userOneId)
                ->where('user_two_id', $userTwoId);
        })->orWhere(function ($query) use ($userOneId, $userTwoId) {
            $query->where('user_one_id', $userTwoId)
                ->where('user_two_id', $userOneId);
        })->first();

        // If no conversation exists, create a new one
        if (!$conversation) {
            $conversation = self::create([
                'user_one_id' => $userOneId,
                'user_two_id' => $userTwoId,
            ]);
        }

        return $conversation;
    }

    public static function between($userOneId, $userTwoId)
    {
        // Find a conversation where userOneId and userTwoId are part of the conversation
        return self::where(function ($query) use ($userOneId, $userTwoId) {
            $query->where('user_one_id', $userOneId)
                ->where('user_two_id', $userTwoId);
        })->orWhere(function ($query) use ($userOneId, $userTwoId) {
            $query->where('user_one_id', $userTwoId)
                ->where('user_two_id', $userOneId);
        })->first();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}
