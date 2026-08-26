<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityHealthCheck extends Model
{
    protected $fillable = ['service','status','message','metadata','checked_at'];

    protected $casts = [
        'metadata' => 'array',
        'checked_at' => 'datetime',
    ];
}
