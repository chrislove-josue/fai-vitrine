<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;

class ApiClient extends Model
{
    use GeneratesUuid;

    protected $connection = 'isp_application';

    protected $fillable = [
        'uuid', 'name', 'client_id', 'secret_hash', 'type', 'status',
        'last_used_at', 'expires_at',
    ];

    protected $hidden = ['secret_hash'];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
