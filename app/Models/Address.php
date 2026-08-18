<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use GeneratesUuid;

    protected $connection = 'isp_core';

    protected $fillable = [
        'uuid', 'customer_id', 'type', 'country', 'region', 'city', 'district',
        'postal_code', 'address_line_1', 'address_line_2', 'latitude', 'longitude',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
