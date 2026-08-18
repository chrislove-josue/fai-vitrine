<?php

use App\Models\AuditLog;
use App\Models\OutboxEvent;
use App\Models\SyncOperation;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

test('toutes les tables isp_application sont créées', function () {
    $tables = [
        'users', 'password_reset_tokens', 'sessions',
        'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions',
        'audit_logs', 'sync_operations', 'outbox_events',
        'external_systems', 'external_references', 'webhook_receipts',
        'api_clients', 'system_settings',
    ];

    foreach ($tables as $table) {
        expect(Schema::connection('isp_application')->hasTable($table))
            ->toBeTrue("Table {$table} absente");
    }
});

test('la table users possède les colonnes du cahier des charges', function () {
    $columns = Schema::connection('isp_application')->getColumnListing('users');

    expect($columns)->toContain('uuid', 'customer_uuid', 'name', 'email', 'phone', 'password', 'status', 'email_verified_at', 'phone_verified_at', 'last_login_at', 'deleted_at');
});

test('le stockage des entités applicatives fonctionne sur isp_application', function () {
    $user = User::factory()->create();

    expect($user->getConnectionName())->toBe('isp_application');
    expect(User::find($user->id)->uuid)->toBe($user->uuid);
});

test('le seeder crée les huit rôles du cahier des charges', function () {
    $this->seed();

    foreach (RoleAndPermissionSeeder::ROLES as $role) {
        expect(Role::where('name', $role)->exists())->toBeTrue("Rôle {$role} manquant");
    }
});

test('un utilisateur peut se voir assigner un rôle et une permission', function () {
    $this->seed();

    $user = User::factory()->create();
    $user->assignRole('finance');

    expect($user->hasRole('finance'))->toBeTrue();
    expect($user->hasPermissionTo('invoice.create'))->toBeTrue();
    expect($user->hasPermissionTo('network.sync'))->toBeFalse();
});

test('un utilisateur super_admin dispose de toutes les permissions', function () {
    $this->seed();

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    expect($user->hasPermissionTo('setting.update'))->toBeTrue();
    expect($user->hasPermissionTo('network.sync'))->toBeTrue();
});

test('les enregistrements applicatifs sont isolés sur leur connexion', function () {
    SyncOperation::create([
        'operation_type' => 'subscription.activated',
        'entity_type' => 'subscription',
        'entity_uuid' => Str::uuid(),
        'destination' => 'freeradius',
    ]);
    AuditLog::create(['action' => 'test', 'auditable_type' => 'subscription', 'auditable_uuid' => Str::uuid()]);
    OutboxEvent::create(['event_type' => 'Test', 'aggregate_type' => 'subscription', 'aggregate_uuid' => Str::uuid()]);

    expect(SyncOperation::count())->toBe(1);
    expect(AuditLog::count())->toBe(1);
    expect(OutboxEvent::count())->toBe(1);
});
