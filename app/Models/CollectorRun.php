<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectorRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * Named collectorQuery(), not query(): an instance method called query()
     * collides with Eloquent's static Model::query() and fatals at class load.
     */
    public function collectorQuery(): BelongsTo
    {
        return $this->belongsTo(CollectorQuery::class, 'collector_query_id');
    }
}
