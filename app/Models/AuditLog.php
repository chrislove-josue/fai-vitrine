<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use GeneratesUuid;

    protected $connection = 'isp_application';

    protected $fillable = [
        'uuid', 'user_uuid', 'action', 'auditable_type', 'auditable_uuid',
        'old_values', 'new_values', 'ip_address', 'user_agent', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }
}
