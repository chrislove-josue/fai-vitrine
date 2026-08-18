<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use GeneratesUuid;

    protected $connection = 'isp_core';

    protected $fillable = ['uuid', 'code', 'channel', 'subject', 'content', 'variables', 'version', 'status'];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
        ];
    }
}
