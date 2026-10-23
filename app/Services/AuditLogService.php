<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public static function log(
        string $action,
        ?string $description = null,
        ?Model $entity = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        $user = auth()->user();

        return AuditLog::create([
            'organization_id' => $user?->organization_id,
            'branch_id' => $user?->branch_id,
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entity ? get_class($entity) : null,
            'entity_id' => $entity?->id,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
