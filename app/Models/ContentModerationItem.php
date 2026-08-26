<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentModerationItem extends Model
{
    protected $table = 'content_moderation_queue';

    protected $fillable = [
        'entity_type','entity_id','reason','priority','status',
        'notes','assigned_to','resolved_by','resolved_at'
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];
}
