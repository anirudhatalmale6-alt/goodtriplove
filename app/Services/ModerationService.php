<?php

namespace App\Services;

use App\Models\ContentModerationItem;

class ModerationService
{
    public function enqueue(
        string $entityType,
        int $entityId,
        string $reason,
        string $priority = 'normal',
        ?string $notes = null
    ): ContentModerationItem {
        return ContentModerationItem::firstOrCreate([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'reason' => $reason,
            'status' => 'pending',
        ], [
            'priority' => $priority,
            'notes' => $notes,
        ]);
    }

    public function resolve(ContentModerationItem $item, int $userId, ?string $notes = null): void
    {
        $item->update([
            'status' => 'resolved',
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'notes' => $notes ?: $item->notes,
        ]);
    }
}
