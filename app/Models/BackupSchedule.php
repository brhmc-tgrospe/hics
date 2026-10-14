<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_enabled',
        'frequency',
        'day_of_week',
        'day_of_month',
        'execution_time',
        'last_run_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'last_run_at' => 'datetime',
    ];
}
