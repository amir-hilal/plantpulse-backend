<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PlantTimeline;
class Plant extends Model
{
    use HasFactory;

    protected $fillable = [
        'garden_id',
        'name',
        'category',
        'age',
        'important_note',
        'last_watered',
        'next_time_to_water',
        'height',
        'health_status',
        'image_url',
    ];

    // Relationship: a plant belongs to a garden
    public function garden()
    {
        return $this->belongsTo(Garden::class);
    }

    // Relationship: a plant has many timeline events
    public function timelines()
    {
        return $this->hasMany(PlantTimeline::class);
    }
}
