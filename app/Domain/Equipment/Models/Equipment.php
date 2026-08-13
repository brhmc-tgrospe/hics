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
        static::saving(function (Equipment $equipment) {
            if ($equipment->quantity_per_property_card !== null && $equipment->quantity_per_physical_count !== null) {
                if ($equipment->shortage_overage_qty === null || ($equipment->isDirty(['quantity_per_property_card', 'quantity_per_physical_count']) && !$equipment->isDirty('shortage_overage_qty'))) {
                    $equipment->shortage_overage_qty = $equipment->quantity_per_property_card - $equipment->quantity_per_physical_count;
                }
            }

            if ($equipment->shortage_overage_qty !== null && $equipment->unit_value !== null) {
                if ($equipment->shortage_overage_value === null || ($equipment->isDirty(['shortage_overage_qty', 'unit_value', 'quantity_per_property_card', 'quantity_per_physical_count']) && !$equipment->isDirty('shortage_overage_value'))) {
                    $equipment->shortage_overage_value = round($equipment->shortage_overage_qty * (float)$equipment->unit_value, 2);
                }
            }

            if ($equipment->quantity_per_physical_count !== null && $equipment->unit_value !== null) {
                if ($equipment->total_value === null || ($equipment->isDirty(['quantity_per_physical_count', 'unit_value']) && !$equipment->isDirty('total_value'))) {
                    $equipment->total_value = round($equipment->quantity_per_physical_count * (float)$equipment->unit_value, 2);
                }
            }
        });
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
