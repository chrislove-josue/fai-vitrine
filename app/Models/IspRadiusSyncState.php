<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;

class IspRadiusSyncState extends Model
{
    use GeneratesUuid;

    protected $connection = 'freeradius';

    protected $table = 'isp_radius_sync_state';

    protected $fillable = [
        'uuid', 'network_account_uuid', 'subscription_uuid', 'desired_status',
        'actual_status', 'desired_profile', 'actual_profile', 'last_sync_at',
        'last_success_at', 'last_failure_at', 'sync_status', 'last_error',
    ];

    protected function casts(): array
    {
        return [
            'last_sync_at' => 'datetime',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
        ];
    }
}
