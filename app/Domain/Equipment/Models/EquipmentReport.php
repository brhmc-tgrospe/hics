<?php

namespace App\Domain\Equipment\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TracksDeletes;

class EquipmentReport extends Model
{
    use LogsActivity, SoftDeletes, TracksDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(function(string $eventName) {
                $type = $this->report_type ?: 'Report';
                $cat = $this->category ? " ({$this->category})" : '';
                return ucfirst($eventName) . " equipment {$type}{$cat}";
            });
    }
    
    public function tapActivity($activity, string $eventName)
    {
        // Infer division_id and area_id from the user who generated the report
        if ($this->user) {
            $activity->properties = $activity->properties->merge([
                'division_id' => $this->user->division_id,
                'area_id' => $this->user->area_id,
            ]);
        }
    }
    protected $fillable = [
        'category',
        'date_of_accountability',
        'year_of_report',
        'file_path',
        'report_type',
        'scope_id',
        'user_id',
    ];

    protected $casts = [
        'date_of_accountability' => 'date',
        'year_of_report' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
