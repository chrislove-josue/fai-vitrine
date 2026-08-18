<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalSystem extends Model
{
    use GeneratesUuid;

    protected $connection = 'isp_application';

    protected $fillable = ['uuid', 'name', 'code', 'type', 'base_url', 'status', 'configuration'];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
        ];
    }

    public function references(): HasMany
    {
        return $this->hasMany(ExternalReference::class);
    }
}
