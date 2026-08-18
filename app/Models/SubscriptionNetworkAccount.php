<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionNetworkAccount extends Model
{
    use GeneratesUuid;

    protected $connection = 'isp_core';

    protected $fillable = ['uuid', 'subscription_id', 'network_account_id', 'starts_at', 'ends_at', 'status'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function networkAccount(): BelongsTo
    {
        return $this->belongsTo(NetworkAccount::class);
    }
}
