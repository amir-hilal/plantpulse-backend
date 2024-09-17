<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'post_id',
        'user_id',
        'comment_text'
    ];

    public function post()
    {
        return $this->belongsTo(CommunityPost::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
