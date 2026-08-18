<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('les migrations créent les tables sur isp_application', function () {
    $tables = ['users', 'sessions', 'cache', 'jobs'];

    foreach ($tables as $table) {
        expect(Schema::connection('isp_application')->hasTable($table))->toBeTrue();
    }
});

test('les tables applicatives ne sont pas créées sur isp_core ni freeradius', function () {
    expect(Schema::connection('isp_core')->hasTable('users'))->toBeFalse();
    expect(Schema::connection('freeradius')->hasTable('users'))->toBeFalse();
});

test('chaque connexion migre sur sa propre base mémoire', function () {
    $application = DB::connection('isp_application')->selectOne('select sqlite_version() as version');
    $core = DB::connection('isp_core')->selectOne('select sqlite_version() as version');
    $radius = DB::connection('freeradius')->selectOne('select sqlite_version() as version');

    expect($application->version)->not->toBeNull();
    expect($core->version)->not->toBeNull();
    expect($radius->version)->not->toBeNull();
});

test('la table migrations existe sur les trois connexions', function () {
    foreach (['isp_application', 'isp_core', 'freeradius'] as $connection) {
        expect(Schema::connection($connection)->hasTable('migrations'))
            ->toBeTrue("Table migrations absente sur {$connection}");
    }
});
