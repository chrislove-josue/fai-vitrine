<?php

use Illuminate\Support\Facades\Config;

test('la connexion par défaut est isp_application', function () {
    expect(config('database.default'))->toBe('isp_application');
});

test('les trois connexions sont déclarées', function () {
    foreach (['isp_application', 'isp_core', 'freeradius'] as $connection) {
        expect(config("database.connections.{$connection}"))
            ->toBeArray()
            ->toHaveKeys(['driver', 'host', 'port', 'database', 'username', 'password']);
    }
});

test('chaque connexion possède un driver valide', function () {
    $allowed = ['mysql', 'pgsql', 'sqlite', 'mariadb', 'sqlsrv'];

    foreach (['isp_application', 'isp_core', 'freeradius'] as $connection) {
        expect(config("database.connections.{$connection}.driver"))
            ->toBeIn($allowed);
    }
});

test('les connexions applicatives sont distinctes les unes des autres', function () {
    $connectionNames = array_keys(config('database.connections'));

    expect($connectionNames)->toContain('isp_application', 'isp_core', 'freeradius');
    expect(count(array_unique($connectionNames)))->toBe(count($connectionNames));
});

test('chaque connexion lit sa propre variable d\'environnement', function () {
    expect(config('database.connections.isp_application.database'))
        ->toBe(env('DB_APPLICATION_DATABASE', 'isp_application'));
    expect(config('database.connections.isp_core.database'))
        ->toBe(env('DB_CORE_DATABASE', 'isp_core'));
    expect(config('database.connections.freeradius.database'))
        ->toBe(env('DB_RADIUS_DATABASE', 'freeradius'));
});

test('les paramètres sont lus depuis les variables d\'environnement', function () {
    Config::set('database.connections.isp_core.database', 'isp_core');

    expect(config('database.connections.isp_core.database'))->toBe('isp_core');
    expect(config('database.connections.isp_application.database'))
        ->toBe(env('DB_APPLICATION_DATABASE', 'isp_application'));
});
