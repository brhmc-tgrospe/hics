<?php

namespace App\Domain\Shared\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
    ];
}
