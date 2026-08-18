<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRule extends Model
{
    use GeneratesUuid;

    protected $connection = 'isp_core';

    protected $fillable = ['uuid', 'event', 'offset_minutes', 'channel', 'template_id', 'status'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class);
    }
}
