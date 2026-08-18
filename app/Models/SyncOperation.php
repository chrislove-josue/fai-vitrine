<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;

class SyncOperation extends Model
{
    use GeneratesUuid;

    protected $connection = 'isp_application';

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'attempts' => 0,
    ];

    protected $fillable = [
        'uuid', 'operation_type', 'entity_type', 'entity_uuid', 'source',
        'destination', 'status', 'attempts', 'payload', 'response',
        'error_code', 'error_message', 'started_at', 'completed_at', 'next_retry_at',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RETRY = 'retry';

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'next_retry_at' => 'datetime',
        ];
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_RETRY]);
    }
}
