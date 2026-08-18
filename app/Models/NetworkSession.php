<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;

class NetworkSession extends Model
{
    use GeneratesUuid;

    protected $connection = 'isp_core';

    protected $fillable = [
        'uuid', 'network_account_uuid', 'username', 'nas_identifier', 'session_id',
        'ip_address', 'mac_address', 'started_at', 'ended_at', 'session_seconds',
        'bytes_in', 'bytes_out', 'terminate_cause',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }
}
