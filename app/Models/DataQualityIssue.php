<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataQualityIssue extends Model
{
    protected $fillable = [
        'issue_type','entity_type','entity_id','severity',
        'message','metadata','status','resolved_by','resolved_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'resolved_at' => 'datetime',
    ];
}
