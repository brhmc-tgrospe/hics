<?php

namespace App\Domain\Supplies\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supply extends Model
{
    use HasFactory;

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
