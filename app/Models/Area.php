<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TracksDeletes;

class Area extends Model
{
    use HasFactory, LogsActivity, SoftDeletes, TracksDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(function(string $eventName) {
                return ucfirst($eventName) . " area: {$this->area_name}";
            });
    }
    
    public function tapActivity($activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'division_id' => $this->division_id,
            'area_id' => $this->id,
        ]);
    }

    protected $fillable = [
        'area_name',
        'division_id',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
