<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use GeneratesUuid;

    protected $connection = 'isp_core';

    protected $fillable = [
        'uuid', 'invoice_id', 'description', 'quantity', 'unit_price',
        'discount', 'tax', 'total', 'reference_type', 'reference_id',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
