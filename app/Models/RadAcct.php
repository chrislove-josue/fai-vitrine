<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadAcct extends Model
{
    protected $connection = 'freeradius';

    protected $table = 'radacct';

    protected $primaryKey = 'radacctid';

    public $timestamps = false;

    protected $fillable = [
        'acctsessionid', 'acctuniqueid', 'username', 'groupname', 'realm',
        'nasipaddress', 'nasportid', 'nasporttype', 'acctstarttime',
        'acctupdatetime', 'acctstoptime', 'acctsessiontime', 'acctauthentic',
        'connectinfo_start', 'connectinfo_stop', 'acctinputoctets',
        'acctoutputoctets', 'calledstationid', 'callingstationid',
        'acctterminatecause', 'servicetype', 'framedprotocol', 'framedipaddress',
    ];

    protected function casts(): array
    {
        return [
            'acctstarttime' => 'datetime',
            'acctupdatetime' => 'datetime',
            'acctstoptime' => 'datetime',
        ];
    }
}
