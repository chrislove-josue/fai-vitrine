<?php

namespace App\Support;

use App\Models\AuditLog;

/**
 * Écriture standardisée des traces d'audit (qui, quoi, quand, depuis où).
 */
class AdminAudit
{
    public static function log(
        string $action,
        ?string $auditableType = null,
        ?string $auditableUuid = null,
        array $metadata = [],
        array $newValues = [],
        array $oldValues = [],
    ): void {
        AuditLog::create([
            'user_uuid' => auth()->user()?->uuid,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_uuid' => $auditableUuid,
            'old_values' => $oldValues !== [] ? $oldValues : null,
            'new_values' => $newValues !== [] ? $newValues : null,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
            'metadata' => $metadata !== [] ? $metadata : null,
        ]);
    }
}
