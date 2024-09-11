<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PlantTimeline;
use Carbon\Carbon;

class Plant extends Model
{
    use HasFactory;
    protected $fillable = [
        'garden_id',
        'name',
        'category',
        'planted_date',
        'important_note',
        'last_watered',
        'next_time_to_water',
        'height',
        'description',
        'health_status',
        'image_url',
        'watering_frequency',

    ];

    // Calculate the plant's age in days
    public function getAgeInDaysAttribute()
    {
        $plantedDate = Carbon::parse($this->planted_date);
        return $plantedDate->diffInDays(Carbon::now());
    }

    // Calculate the plant's age in human-readable form (years, weeks, days)
    public function getFormattedAgeAttribute()
    {
        $plantedDate = Carbon::parse($this->planted_date);
        $diffInDays = $plantedDate->diffInDays(Carbon::now());

        $years = floor($diffInDays / 365);
        $weeks = floor(($diffInDays % 365) / 7);
        $days = $diffInDays % 7;

        $ageString = '';
        if ($years > 0) {
            $ageString .= "$years year" . ($years > 1 ? 's' : '') . " ";
        }
        if ($weeks > 0) {
            $ageString .= "$weeks week" . ($weeks > 1 ? 's' : '') . " ";
        }
        if ($days > 0) {
            $ageString .= "$days day" . ($days > 1 ? 's' : '');
        }

        return trim($ageString);
    }


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

    public function wateringCount()
    {
        return $this->hasMany(WateringEvent::class)->where('is_done', true)->count();
    }

}
