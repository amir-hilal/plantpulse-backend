<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TutorialComment;
class Tutorial extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'video_url',
        'tags',
    ];

    // Cast the 'tags' attribute to array
    protected $casts = [
        'tags' => 'array',
    ];

    // A tutorial has many comments
    public function comments()
    {
        return $this->hasMany(TutorialComment::class);
    }
}
