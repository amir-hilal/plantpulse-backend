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

    public static function findOrCreate($userIdOne, $userIdTwo)
    {
        $userIds = [$userIdOne, $userIdTwo];
        sort($userIds);

        $conversation = self::where('user_one_id', $userIds[0])
            ->where('user_two_id', $userIds[1])
            ->first();

        if ($conversation) {
            return $conversation;
        }

        return self::create([
            'user_one_id' => $userIds[0],
            'user_two_id' => $userIds[1],
        ]);
    }
}
