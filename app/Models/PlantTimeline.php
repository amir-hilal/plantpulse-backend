<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantTimeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'plant_id',
        'description',
        'image_path',
        'source',
    ];

    // Relationship: a timeline belongs to a plant
    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }
}
