<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerDocument extends Model
{
    use GeneratesUuid, SoftDeletes;

    protected $connection = 'isp_core';

    protected $fillable = [
        'uuid', 'customer_id', 'type', 'file_name', 'file_path', 'mime_type',
        'file_size', 'status', 'verified_by', 'verified_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
