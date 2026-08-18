<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;

class OutboxEvent extends Model
{
    use GeneratesUuid;

    protected $connection = 'isp_application';

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'attempts' => 0,
    ];

    protected $fillable = [
        'uuid', 'event_type', 'aggregate_type', 'aggregate_uuid', 'payload',
        'status', 'attempts', 'available_at', 'processed_at', 'failed_at', 'error_message',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'available_at' => 'datetime',
            'processed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('available_at')->orWhere('available_at', '<=', now());
            });
    }
}
