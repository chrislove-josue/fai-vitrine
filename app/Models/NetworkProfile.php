<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NetworkProfile extends Model
{
    use GeneratesUuid, HasFactory;

    protected $connection = 'isp_core';

    protected $fillable = [
        'uuid', 'code', 'name', 'download_speed', 'upload_speed', 'rate_limit',
        'burst_limit', 'burst_threshold', 'burst_time', 'priority',
        'session_timeout', 'idle_timeout', 'data_limit', 'status',
    ];

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
