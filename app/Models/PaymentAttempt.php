<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    use GeneratesUuid;

    protected $connection = 'isp_core';

    protected $fillable = [
        'uuid', 'payment_id', 'provider', 'request_id', 'amount', 'currency',
        'status', 'response_code', 'response_message', 'provider_reference',
        'provider_payload', 'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'provider_payload' => 'array',
            'attempted_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
