<?php

namespace App\Domain\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TracksDeletes;

class Category extends Model
{
    use SoftDeletes, TracksDeletes;
    protected $fillable = [
        'code',
        'name',
        'type',
        'has_expiration_date',
    ];

    protected $casts = [
        'has_expiration_date' => 'boolean',
    ];
}
