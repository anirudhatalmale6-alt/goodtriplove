<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentReport extends Model
{
    protected $fillable = [
        'reporter_user_id','target_type','target_id','reason','details',
        'status','reviewed_by','reviewed_at'
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];
}
