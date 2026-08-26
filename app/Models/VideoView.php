<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoView extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['viewed_on' => 'date'];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
