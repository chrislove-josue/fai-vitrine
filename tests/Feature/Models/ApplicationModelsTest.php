<?php

use App\Models\ApiClient;
use App\Models\AuditLog;
use App\Models\OutboxEvent;
use App\Models\SyncOperation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

test('un utilisateur génère automatiquement un uuid unique', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    expect($a->uuid)->toBeUuid();
    expect($a->uuid)->not->toBe($b->uuid);
});

test('le mot de passe utilisateur est haché', function () {
    $user = User::factory()->create(['password' => 'SuperSecret@2026']);

    expect(Hash::check('SuperSecret@2026', $user->password))->toBeTrue();
    expect($user->password)->not->toBe('SuperSecret@2026');
});

test('le mot de passe et le remember token ne sont pas exposés', function () {
    $user = User::factory()->create();

    $serialized = $user->toArray();

    expect($serialized)->not->toHaveKey('password');
    expect($serialized)->not->toHaveKey('remember_token');
});

test('le secret d\'un api client n\'est pas exposé et est haché', function () {
    $client = ApiClient::create([
        'name' => 'FreeRADIUS Sync',
        'client_id' => 'radius-sync',
        'secret_hash' => Hash::make('s3cr3t'),
    ]);

    expect(Hash::check('s3cr3t', $client->secret_hash))->toBeTrue();
    expect($client->toArray())->not->toHaveKey('secret_hash');
});

test('audit_logs sérialise correctement les valeurs avant/après', function () {
    $log = AuditLog::create([
        'action' => 'subscription.suspend',
        'auditable_type' => 'subscription',
        'auditable_uuid' => Str::uuid(),
        'old_values' => ['status' => 'active'],
        'new_values' => ['status' => 'suspended'],
    ]);

    expect($log->old_values)->toBe(['status' => 'active']);
    expect($log->new_values)->toBe(['status' => 'suspended']);
    expect($log->uuid)->toBeUuid();
});

test('sync_operations expose un scope pending couvrant pending et retry', function () {
    SyncOperation::create(['operation_type' => 'sync', 'entity_type' => 'subscription', 'entity_uuid' => Str::uuid(), 'destination' => 'freeradius', 'status' => 'pending']);
    SyncOperation::create(['operation_type' => 'sync', 'entity_type' => 'subscription', 'entity_uuid' => Str::uuid(), 'destination' => 'freeradius', 'status' => 'retry']);
    SyncOperation::create(['operation_type' => 'sync', 'entity_type' => 'subscription', 'entity_uuid' => Str::uuid(), 'destination' => 'freeradius', 'status' => 'success']);

    expect(SyncOperation::pending()->count())->toBe(2);
});

test('outbox_events sérialise le payload en json', function () {
    $event = OutboxEvent::create([
        'event_type' => 'SubscriptionActivated',
        'aggregate_type' => 'subscription',
        'aggregate_uuid' => Str::uuid(),
        'payload' => ['status' => 'active'],
    ]);

    expect($event->payload)->toBe(['status' => 'active']);
    expect($event->status)->toBe('pending');
    expect($event->uuid)->toBeUuid();
});
