<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionRenewal extends Model
{
    use GeneratesUuid;

    protected $connection = 'isp_core';

    protected $fillable = [
        'uuid', 'subscription_id', 'invoice_id', 'payment_id',
        'old_expires_at', 'new_expires_at', 'amount', 'currency', 'status',
    ];

    protected function casts(): array
    {
        return [
            'old_expires_at' => 'datetime',
            'new_expires_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
