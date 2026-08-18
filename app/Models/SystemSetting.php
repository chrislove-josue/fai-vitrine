<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $connection = 'isp_application';

    protected $fillable = ['key', 'value', 'type', 'group', 'is_encrypted'];

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }
}
