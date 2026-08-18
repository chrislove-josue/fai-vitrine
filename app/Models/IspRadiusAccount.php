<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;

class IspRadiusAccount extends Model
{
    use GeneratesUuid;

    protected $connection = 'freeradius';

    protected $table = 'isp_radius_accounts';

    protected $fillable = [
        'uuid', 'customer_uuid', 'network_account_uuid', 'username',
        'subscription_uuid', 'network_profile_uuid', 'status', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }
}
