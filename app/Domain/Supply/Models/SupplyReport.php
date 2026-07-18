<?php

namespace App\Domain\Supply\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplyReport extends Model
{
    /** @use HasFactory<\Database\Factories\Domain\Supply\Models\SupplyReportFactory> */
    use HasFactory;

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
