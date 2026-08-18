<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use GeneratesUuid, HasFactory, SoftDeletes;

    protected $connection = 'isp_core';

    protected $fillable = [
        'uuid', 'code', 'name', 'description', 'status', 'duration_days',
        'network_profile_id', 'activation_fee', 'currency',
        'max_simultaneous_sessions', 'data_limit', 'fair_use_limit',
    ];

    public function networkProfile(): BelongsTo
    {
        return $this->belongsTo(NetworkProfile::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(OfferPrice::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentPrice(): ?OfferPrice
    {
        return $this->prices()
            ->where('starts_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->orderByDesc('starts_at')
            ->first();
    }
}
