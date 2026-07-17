<?php

namespace App\Domain\Supplies\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supply extends Model
{
    use HasFactory;

    protected $table = 'supplies';

    protected $guarded = ['id'];
}
