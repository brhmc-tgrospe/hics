<?php

namespace App\Domain\Equipment\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentReport extends Model
{
    protected $fillable = [
        'category',
        'date_of_accountability',
        'year_of_report',
        'file_path',
        'report_type',
        'scope_id',
    ];

    protected $casts = [
        'date_of_accountability' => 'date',
        'year_of_report' => 'integer',
    ];
}
