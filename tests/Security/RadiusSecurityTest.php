<?php

use App\Models\IspRadiusAccount;
use Illuminate\Support\Facades\Schema;

test('freeradius ne stocke aucune donnée à caractère personnel', function () {
    $radiusColumns = array_merge(
        Schema::connection('freeradius')->getColumnListing('radcheck'),
        Schema::connection('freeradius')->getColumnListing('radreply'),
        Schema::connection('freeradius')->getColumnListing('isp_radius_accounts'),
        Schema::connection('freeradius')->getColumnListing('radacct'),
    );

    expect($radiusColumns)->not->toContain('email');
    expect($radiusColumns)->not->toContain('phone');
    expect($radiusColumns)->not->toContain('first_name');
    expect($radiusColumns)->not->toContain('last_name');
    expect($radiusColumns)->not->toContain('birth_date');
});

test('seules des références logiques (uuid) relient freeradius au système commercial', function () {
    $columns = Schema::connection('freeradius')->getColumnListing('isp_radius_accounts');

    expect($columns)->toContain('customer_uuid', 'network_account_uuid', 'subscription_uuid', 'network_profile_uuid');
    expect($columns)->not->toContain('customer_id');
});

test('un compte réseau suspendu est marqué avec un statut explicite, jamais supprimé', function () {
    $account = IspRadiusAccount::create([
        'network_account_uuid' => '01abc-susp',
        'username' => 'suspend-me',
        'status' => 'suspended',
    ]);

    expect(IspRadiusAccount::where('username', 'suspend-me')->count())->toBe(1);
    expect($account->status)->toBe('suspended');
});

test('les sessions réseau ne contiennent pas de mot de passe', function () {
    $columns = Schema::connection('freeradius')->getColumnListing('radacct');

    expect($columns)->not->toContain('password');
    expect($columns)->not->toContain('cleartext');
});
