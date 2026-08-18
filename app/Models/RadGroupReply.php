<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadGroupReply extends Model
{
    protected $connection = 'freeradius';

    protected $table = 'radgroupreply';

    protected $fillable = ['groupname', 'attribute', 'op', 'value'];
}
