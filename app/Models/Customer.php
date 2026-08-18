<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use GeneratesUuid, HasFactory, SoftDeletes;

    protected $connection = 'isp_core';

    protected $fillable = [
        'uuid', 'customer_number', 'type', 'status',
        'first_name', 'last_name', 'company_name', 'email', 'phone', 'birth_date',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function networkAccounts(): HasMany
    {
        return $this->hasMany(NetworkAccount::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->type === 'individual') {
            return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
        }

        return $this->company_name ?? $this->first_name ?? $this->last_name ?? $this->customer_number;
    }
}
