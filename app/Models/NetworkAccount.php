<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NetworkAccount extends Model
{
    use GeneratesUuid, HasFactory;

    protected $connection = 'isp_core';

    protected $fillable = [
        'uuid', 'customer_id', 'username', 'authentication_type', 'status', 'mac_auth_enabled',
    ];

    protected function casts(): array
    {
        return [
            'mac_auth_enabled' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscriptionLinks(): HasMany
    {
        return $this->hasMany(SubscriptionNetworkAccount::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(CustomerDevice::class);
    }
}
