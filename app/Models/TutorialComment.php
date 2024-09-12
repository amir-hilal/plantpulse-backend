<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TutorialComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tutorial_id',
        'user_id',
        'comment',
    ];

    // A comment belongs to a tutorial
    public function tutorial()
    {
        return $this->belongsTo(Tutorial::class);
    }

    // A comment belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
