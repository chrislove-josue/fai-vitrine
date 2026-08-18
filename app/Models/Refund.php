<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use GeneratesUuid;

    protected $connection = 'isp_core';

    protected $fillable = [
        'uuid', 'payment_id', 'invoice_id', 'amount', 'currency', 'reason',
        'status', 'provider_reference', 'processed_by', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
