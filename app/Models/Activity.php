<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    protected static function booted()
    {
        static::creating(function ($activity) {
            if (empty($activity->ip_address) && request()) {
                $activity->ip_address = request()->ip();
            }
        });
    }
}
