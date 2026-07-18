<?php

namespace App\Domain\Supply\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class SupplyReport extends Model
{
    /** @use HasFactory<\Database\Factories\Domain\Supply\Models\SupplyReportFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
    
    public function tapActivity($activity, string $eventName)
    {
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
        'fund_cluster',
        'file_path',
        'report_type',
        'report_period',
        'custom_month',
        'scope_id',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
