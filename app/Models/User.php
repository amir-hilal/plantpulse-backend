<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Auth\Passwords\CanResetPassword;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, CanResetPassword;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'profile_photo_url',
        'cover_photo_url',
        'about',
        'phone_number',
        'gender',
        'birthday',
        'address',
        'google_id',
        'role',
        'email_verified_at',
        'google_id',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'deleted_at',
        'role',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'birthday' => 'date',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Friend relationship: it checks both directions of friendships.
     * This relationship retrieves all friends where the current user is either the sender or receiver.
     */
    public function friends()
    {
        return $this->belongsToMany(User::class, 'friends', 'user_id', 'friend_id')
            ->withPivot('status')
            ->where(function ($query) {
                $query->where('friends.user_id', auth()->id())
                    ->orWhere('friends.friend_id', auth()->id());
            });
    }

    /**
     * Friend requests received by the user.
     */
    public function friendRequests()
    {
        return $this->belongsToMany(User::class, 'friends', 'friend_id', 'user_id')
            ->withPivot('status');
    }

    /**
     * Fetch all friendships including sent and received friend requests.
     */
    public function allFriendships()
    {
        $sentFriendships = $this->friends()->select('friends.*')->toBase();
        $receivedFriendships = $this->friendRequests()->select('friends.*')->toBase();

        return $this->newQuery()
            ->fromSub($sentFriendships->union($receivedFriendships), 'friendships')
            ->get();
    }
}
