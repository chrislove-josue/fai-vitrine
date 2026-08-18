<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookReceipt extends Model
{
    use GeneratesUuid;

    protected $connection = 'isp_application';

    protected $attributes = [
        'status' => 'received',
    ];

    protected $fillable = [
        'uuid', 'external_system_id', 'event', 'external_id', 'signature',
        'payload', 'status', 'processed_at', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function externalSystem(): BelongsTo
    {
        return $this->belongsTo(ExternalSystem::class);
    }
}
