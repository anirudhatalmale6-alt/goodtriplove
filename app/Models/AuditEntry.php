<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditEntry extends Model
{
    protected $fillable = [
        'actor_user_id','action','auditable_type','auditable_id',
        'old_values','new_values','ip_address','user_agent','success',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'success' => 'boolean',
    ];

    // Pas de SoftDeletes: les logs doivent être conservés.

    /** Who performed the action. Null for anything done by the scheduler. */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * The fields that actually changed, old => new. Comparing rather than
     * dumping both blobs keeps an entry readable when a form posts twenty
     * fields and one of them moved.
     */
    public function changes(): array
    {
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];
        $changed = [];

        foreach (array_keys($old + $new) as $key) {
            $before = $old[$key] ?? null;
            $after = $new[$key] ?? null;

            if ($before !== $after) {
                $changed[$key] = [$before, $after];
            }
        }

        return $changed;
    }
}
