<?php

use App\Models\User;
use App\Support\Portal;

test('le rôle client est mappé vers l\'espace client', function () {
    expect(Portal::routeForRole('client'))->toBe('dashboard.index');
});

test('tout rôle staff est mappé vers l\'administration', function () {
    foreach (['super_admin', 'admin', 'finance', 'commercial', 'support', 'network_admin', 'operator'] as $role) {
        expect(Portal::routeForRole($role))->toBe('admin.index');
    }
});

test('un rôle inconnu est traité comme staff (défaut sécurisé)', function () {
    expect(Portal::routeForRole('unknown'))->toBe('admin.index');
});

test('la distinction staff/client est correcte', function () {
    expect(Portal::isStaffRole('client'))->toBeFalse();
    expect(Portal::isStaffRole('admin'))->toBeTrue();
    expect(Portal::isStaffRole('finance'))->toBeTrue();
});

test('les rôles staff du cahier des charges couvrent l\'ensemble attendu', function () {
    $expected = ['super_admin', 'admin', 'finance', 'commercial', 'support', 'network_admin', 'operator'];

    expect(array_diff($expected, User::STAFF_ROLES))->toBe([]);
    expect(in_array('client', User::STAFF_ROLES, true))->toBeFalse();
});
