<?php

namespace App\Services;

use App\Models\AuditEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    public function record(
        string $action,
        ?Model $model = null,
        array $old = [],
        array $new = [],
        bool $success = true,
        ?Request $request = null
    ): void {
        $request ??= request();

        AuditEntry::create([
            'actor_user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id' => $model?->getKey(),
            'old_values' => $this->sanitize($old),
            'new_values' => $this->sanitize($new),
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string)$request?->userAgent(),0,1000),
            'success' => $success,
        ]);
    }

    private function sanitize(array $values): array
    {
        $blocked = ['password','password_confirmation','token','secret','api_key','authorization'];

        foreach ($values as $k => $v) {
            if (in_array(strtolower((string)$k), $blocked, true)) {
                $values[$k] = '[REDACTED]';
            }
        }

        return $values;
    }
}
