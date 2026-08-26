<?php

namespace App\Services;

use App\Models\DataQualityIssue;

class DataQualityService
{
    public function report(
        string $issueType,
        string $entityType,
        ?int $entityId,
        string $message,
        string $severity = 'warning',
        array $metadata = []
    ): void {
        DataQualityIssue::firstOrCreate([
            'issue_type' => $issueType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'status' => 'open',
        ], [
            'severity' => $severity,
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }

    public function resolve(DataQualityIssue $issue, int $userId): void
    {
        $issue->update([
            'status' => 'resolved',
            'resolved_by' => $userId,
            'resolved_at' => now(),
        ]);
    }
}
