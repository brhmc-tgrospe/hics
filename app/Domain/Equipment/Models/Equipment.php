<?php

namespace App\Domain\Equipment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TracksDeletes;

class Equipment extends Model
{
    use HasFactory, LogsActivity, SoftDeletes, TracksDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logUnguarded()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(function(string $eventName) {
                $article = $this->article ?: 'Equipment';
                $category = $this->category ? " ({$this->category})" : '';
                return ucfirst($eventName) . " equipment: {$article}{$category}";
            });
    }
    
    public function tapActivity($activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'division_id' => $this->division_id,
            'area_id' => $this->area_id,
        ]);
    }

    protected $table = 'equipment';

    protected $guarded = ['id'];

    public function division()
    {
        return $this->belongsTo(\App\Models\Division::class);
    }

    public function area()
    {
        return $this->belongsTo(\App\Models\Area::class);
    }
}
