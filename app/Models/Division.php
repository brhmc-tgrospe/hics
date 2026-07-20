<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

class Division extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
    
    public function tapActivity($activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'division_id' => $this->id,
            'area_id' => null,
        ]);
    }

    protected $fillable = ['div_code', 'div_name'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function areas()
    {
        return $this->hasMany(Area::class);
    }

    public function equipment()
    {
        return $this->hasMany(\App\Domain\Equipment\Models\Equipment::class);
    }

    public function supplies()
    {
        return $this->hasMany(\App\Domain\Supplies\Models\Supply::class);
    }
}
