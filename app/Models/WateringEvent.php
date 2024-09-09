<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WateringEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'plant_id',
        'scheduled_date',
        'is_done',
        'completed_at',
    ];

    // Relationship: a watering event belongs to a plant
    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }
}
