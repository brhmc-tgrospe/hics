<?php

namespace App\Domain\Supplies\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TracksDeletes;

class Supply extends Model
{
    use HasFactory, LogsActivity, SoftDeletes, TracksDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logUnguarded()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(function(string $eventName) {
                $article = $this->article ?: 'Supply';
                $category = $this->category ? " ({$this->category})" : '';
                return ucfirst($eventName) . " supply: {$article}{$category}";
            });
    }
    
    public function beforeActivityLogged($activity, string $eventName)
    {
        $areaName = $this->area ? $this->area->area_name : $this->area()->withTrashed()->value('area_name');
        $divName = $this->division ? $this->division->div_name : $this->division()->withTrashed()->value('div_name');

        $activity->properties = $activity->properties->merge([
            'division_id' => $this->division_id,
            'area_id' => $this->area_id,
            'area_name' => $areaName,
            'div_name' => $divName,
        ]);
    }

    protected static function booted()
    {
        static::saving(function (Supply $supply) {
            if ($supply->balance_per_card !== null && $supply->on_hand_per_count !== null) {
                if ($supply->shortage_overage_qty === null || ($supply->isDirty(['balance_per_card', 'on_hand_per_count']) && !$supply->isDirty('shortage_overage_qty'))) {
                    $supply->shortage_overage_qty = $supply->balance_per_card - $supply->on_hand_per_count;
                }
            }

            if ($supply->shortage_overage_qty !== null && $supply->unit_value !== null) {
                if ($supply->shortage_overage_value === null || ($supply->isDirty(['shortage_overage_qty', 'unit_value', 'balance_per_card', 'on_hand_per_count']) && !$supply->isDirty('shortage_overage_value'))) {
                    $supply->shortage_overage_value = round($supply->shortage_overage_qty * (float)$supply->unit_value, 2);
                }
            }

            if ($supply->on_hand_per_count !== null && $supply->unit_value !== null) {
                if ($supply->total_amount === null || ($supply->isDirty(['on_hand_per_count', 'unit_value']) && !$supply->isDirty('total_amount'))) {
                    $supply->total_amount = round($supply->on_hand_per_count * (float)$supply->unit_value, 2);
                }
            }
        });
    }

    protected $table = 'supplies';

    protected $guarded = ['id'];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function division()
    {
        return $this->belongsTo(\App\Models\Division::class);
    }

    public function area()
    {
        return $this->belongsTo(\App\Models\Area::class);
    }
}
