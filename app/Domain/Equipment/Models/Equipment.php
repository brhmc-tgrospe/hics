<?php

namespace App\Domain\Equipment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Equipment extends Model
{
    use HasFactory;

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
