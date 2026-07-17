<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $fillable = ['div_code', 'div_name'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function areas()
    {
        return $this->hasMany(Area::class);
    }
}
