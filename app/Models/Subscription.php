<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use GeneratesUuid, HasFactory, SoftDeletes;

    protected $connection = 'isp_core';

    protected $fillable = [
        'uuid', 'subscription_number', 'customer_id', 'offer_id', 'status',
        'starts_at', 'expires_at', 'activated_at', 'suspended_at', 'cancelled_at',
        'terminated_at', 'auto_renew', 'price', 'currency', 'next_renewal_at',
        'suspension_reason', 'cancellation_reason', 'termination_reason',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_GRACE_PERIOD = 'grace_period';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_TERMINATED = 'terminated';

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'terminated_at' => 'datetime',
            'next_renewal_at' => 'datetime',
            'auto_renew' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SubscriptionEvent::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(SubscriptionRenewal::class);
    }

    public function networkAccountLinks(): HasMany
    {
        return $this->hasMany(SubscriptionNetworkAccount::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
