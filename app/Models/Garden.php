<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Garden extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'name', 'image_url', 'location'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plants()
    {
        return $this->hasMany(Plant::class);
    }
}
