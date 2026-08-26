<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceHealth extends Model
{
    protected $table = 'service_health';

    protected $fillable = ['service','status','message','metadata','checked_at'];

    protected $casts = [
        'metadata' => 'array',
        'checked_at' => 'datetime',
    ];
}
