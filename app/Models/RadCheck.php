<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadCheck extends Model
{
    protected $connection = 'freeradius';

    protected $table = 'radcheck';

    protected $fillable = ['username', 'attribute', 'op', 'value'];
}
