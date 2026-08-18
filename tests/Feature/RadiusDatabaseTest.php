<?php

use App\Models\IspRadiusAccount;
use App\Models\Nas;
use App\Models\RadAcct;
use App\Models\RadCheck;
use App\Models\RadGroupReply;
use App\Models\RadReply;
use Illuminate\Support\Facades\Schema;

test('toutes les tables freeradius sont créées', function () {
    $tables = [
        'radcheck', 'radreply', 'radgroupcheck', 'radgroupreply', 'radusergroup',
        'radacct', 'nas', 'isp_radius_accounts', 'isp_radius_sync_state',
    ];

    foreach ($tables as $table) {
        expect(Schema::connection('freeradius')->hasTable($table))
            ->toBeTrue("Table {$table} absente sur freeradius");
    }
});

test('la table radcheck stocke les attributs standard FreeRADIUS', function () {
    $columns = Schema::connection('freeradius')->getColumnListing('radcheck');

    expect($columns)->toContain('id', 'username', 'attribute', 'op', 'value');
});

test('la table radacct suit le schéma de comptabilité FreeRADIUS', function () {
    $columns = Schema::connection('freeradius')->getColumnListing('radacct');

    expect($columns)->toContain(
        'radacctid', 'acctsessionid', 'acctuniqueid', 'username', 'nasipaddress',
        'acctstarttime', 'acctstoptime', 'acctsessiontime',
        'acctinputoctets', 'acctoutputoctets', 'acctterminatecause',
    );
});

test('les entités freeradius sont enregistrées sur la connexion freeradius', function () {
    $check = RadCheck::create(['username' => 'user1', 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => 'secret']);
    $reply = RadReply::create(['username' => 'user1', 'attribute' => 'Framed-Pool', 'op' => '=', 'value' => 'POOL1']);
    RadGroupReply::create(['groupname' => 'FIBRE20', 'attribute' => 'Mikrotik-Rate-Limit', 'op' => '=', 'value' => '20M/20M']);
    $acct = RadAcct::create([
        'acctsessionid' => 'sess-1',
        'acctuniqueid' => 'uniq-1',
        'username' => 'user1',
        'nasipaddress' => '10.0.0.1',
        'acctsessiontime' => 3600,
        'acctinputoctets' => 1000,
        'acctoutputoctets' => 2000,
    ]);
    $nas = Nas::create(['nasname' => '192.168.1.1', 'shortname' => 'CCR01', 'type' => 'mikrotik', 'secret' => 'nas-secret']);

    expect($check->getConnectionName())->toBe('freeradius');
    expect($reply->getConnectionName())->toBe('freeradius');
    expect($acct->radacctid)->not->toBeNull();
    expect($nas->secret)->toBe('nas-secret');
});

test('isp_radius_accounts stocke les références logiques uuid sans FK inter-base', function () {
    $account = IspRadiusAccount::create([
        'customer_uuid' => '01abc-customer',
        'network_account_uuid' => '01abc-account',
        'username' => 'fiber20-user',
        'subscription_uuid' => '01abc-subscription',
        'network_profile_uuid' => '01abc-profile',
        'status' => 'active',
    ]);

    expect($account->uuid)->toBeUuid();
    expect($account->customer_uuid)->toBe('01abc-customer');
    expect($account->getConnectionName())->toBe('freeradius');
});

test('freeradius ne contient aucune donnée commerciale isp_core', function () {
    expect(Schema::connection('freeradius')->hasTable('customers'))->toBeFalse();
    expect(Schema::connection('freeradius')->hasTable('subscriptions'))->toBeFalse();
    expect(Schema::connection('freeradius')->hasTable('invoices'))->toBeFalse();
});

test('le secret des équipements nas n\'est pas exposé', function () {
    $nas = Nas::create(['nasname' => '10.0.0.5', 'shortname' => 'CCR02', 'type' => 'mikrotik', 'secret' => 'S3cretNas']);

    expect($nas->toArray())->not->toHaveKey('secret');
    expect($nas->secret)->toBe('S3cretNas');
});
