<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalReference extends Model
{
    use GeneratesUuid;

    protected $connection = 'isp_application';

    protected $fillable = [
        'uuid', 'external_system_id', 'entity_type', 'entity_uuid',
        'external_id', 'external_reference', 'sync_status', 'last_synced_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    public function externalSystem(): BelongsTo
    {
        return $this->belongsTo(ExternalSystem::class);
    }
}
