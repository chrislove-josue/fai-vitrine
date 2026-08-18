<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nas extends Model
{
    protected $connection = 'freeradius';

    protected $table = 'nas';

    protected $hidden = ['secret'];

    protected $fillable = [
        'nasname', 'shortname', 'type', 'ports', 'secret', 'server', 'community', 'description',
    ];
}
