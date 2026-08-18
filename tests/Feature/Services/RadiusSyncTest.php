<?php

use App\Models\Customer;
use App\Models\IspRadiusAccount;
use App\Models\IspRadiusSyncState;
use App\Models\NetworkAccount;
use App\Models\OutboxEvent;
use App\Models\RadCheck;
use App\Models\RadUserGroup;
use App\Models\Subscription;
use App\Models\SubscriptionNetworkAccount;
use App\Models\SyncOperation;
use App\Services\OutboxService;
use App\Services\RadiusSyncService;
use App\Services\SubscriptionLifecycleService;

function radiusSubscription(): Subscription
{
    $customer = Customer::factory()->create();
    $networkAccount = NetworkAccount::factory()->create(['customer_id' => $customer->id, 'username' => 'fiber-'.fake()->unique()->numberBetween(1000, 9999)]);
    $subscription = Subscription::factory()->create(['customer_id' => $customer->id]);

    SubscriptionNetworkAccount::create([
        'subscription_id' => $subscription->id,
        'network_account_id' => $networkAccount->id,
        'status' => 'active',
    ]);

    return $subscription;
}

function publishEvent(string $eventType, string $aggregateUuid): OutboxEvent
{
    return app(OutboxService::class)->publish($eventType, 'subscription', $aggregateUuid, ['status' => 'sync']);
}

test('l\'activation crée le compte sur FreeRADIUS et trace l\'opération', function () {
    $subscription = radiusSubscription();
    $networkAccount = $subscription->networkAccountLinks()->first()->networkAccount;

    publishEvent('SubscriptionActivated', (string) $subscription->uuid);
    $result = app(RadiusSyncService::class)->processPending();

    expect($result['processed'])->toBe(1);
    expect($result['failed'])->toBe(0);

    $radiusAccount = IspRadiusAccount::where('username', $networkAccount->username)->first();
    expect($radiusAccount)->not->toBeNull();
    expect($radiusAccount->status)->toBe('active');
    expect($radiusAccount->network_account_uuid)->toBe((string) $networkAccount->uuid);
    expect($radiusAccount->customer_uuid)->toBe((string) $networkAccount->customer->uuid);
    expect($radiusAccount->subscription_uuid)->toBe((string) $subscription->uuid);

    $password = RadCheck::where('username', $networkAccount->username)->where('attribute', 'Cleartext-Password')->first();
    expect($password)->not->toBeNull();
    expect($password->value)->not->toBeEmpty();

    expect(RadCheck::where('username', $networkAccount->username)->where('attribute', 'Auth-Type')->exists())->toBeFalse();

    expect(IspRadiusSyncState::where('network_account_uuid', $networkAccount->uuid)->first()->sync_status)->toBe('synced');
    expect(SyncOperation::where('entity_uuid', $subscription->uuid)->where('status', 'success')->count())->toBe(1);
    expect(OutboxEvent::where('id', OutboxEvent::first()->id)->first()->status)->toBe('completed');
});

test('la suspension ajoute Auth-Type := Reject sans supprimer le compte', function () {
    $subscription = radiusSubscription();
    $networkAccount = $subscription->networkAccountLinks()->first()->networkAccount;
    $username = $networkAccount->username;

    publishEvent('SubscriptionActivated', (string) $subscription->uuid);
    publishEvent('SubscriptionSuspended', (string) $subscription->uuid);

    $result = app(RadiusSyncService::class)->processPending();

    expect($result['processed'])->toBe(2);

    $reject = RadCheck::where('username', $username)->where('attribute', 'Auth-Type')->first();
    expect($reject->value)->toBe('Reject');

    expect(IspRadiusAccount::where('username', $username)->first()->status)->toBe('suspended');
    expect(IspRadiusSyncState::where('network_account_uuid', $networkAccount->uuid)->first()->desired_status)->toBe('suspended');
});

test('la période de grâce maintient le service actif', function () {
    $subscription = radiusSubscription();
    $networkAccount = $subscription->networkAccountLinks()->first()->networkAccount;
    $username = $networkAccount->username;

    publishEvent('SubscriptionActivated', (string) $subscription->uuid);
    publishEvent('SubscriptionExpired', (string) $subscription->uuid);

    app(RadiusSyncService::class)->processPending();

    expect(RadCheck::where('username', $username)->where('attribute', 'Auth-Type')->exists())->toBeFalse();
    expect(IspRadiusSyncState::where('network_account_uuid', $networkAccount->uuid)->first()->desired_status)->toBe('grace_period');
});

test('la résiliation supprime le compte du NAS', function () {
    $subscription = radiusSubscription();
    $networkAccount = $subscription->networkAccountLinks()->first()->networkAccount;
    $username = $networkAccount->username;

    publishEvent('SubscriptionActivated', (string) $subscription->uuid);
    publishEvent('SubscriptionTerminated', (string) $subscription->uuid);

    app(RadiusSyncService::class)->processPending();

    expect(RadCheck::where('username', $username)->count())->toBe(0);
    expect(RadUserGroup::where('username', $username)->count())->toBe(0);
    expect(IspRadiusAccount::where('username', $username)->first()->status)->toBe('terminated');
    expect(IspRadiusSyncState::where('network_account_uuid', $networkAccount->uuid)->first()->desired_status)->toBe('terminated');
});

test('le pipeline complet du cycle de vie aboutit à un état cohérent sur FreeRADIUS', function () {
    $subscription = radiusSubscription();
    $networkAccount = $subscription->networkAccountLinks()->first()->networkAccount;
    $username = $networkAccount->username;
    $lifecycle = app(SubscriptionLifecycleService::class);
    $sync = app(RadiusSyncService::class);

    $lifecycle->activate($subscription);
    $sync->processPending();
    expect(IspRadiusAccount::where('username', $username)->first()->status)->toBe('active');

    $lifecycle->suspend($subscription, reason: 'unpaid');
    $sync->processPending();
    expect(IspRadiusAccount::where('username', $username)->first()->status)->toBe('suspended');

    $lifecycle->reactivate($subscription);
    $sync->processPending();
    expect(IspRadiusAccount::where('username', $username)->first()->status)->toBe('active');
    expect(RadCheck::where('username', $username)->where('attribute', 'Auth-Type')->exists())->toBeFalse();

    $lifecycle->terminate($subscription, reason: 'customer_request');
    $sync->processPending();
    expect(IspRadiusAccount::where('username', $username)->first()->status)->toBe('terminated');
    expect(RadCheck::where('username', $username)->count())->toBe(0);
});

test('la commande radius:sync traite les événements en attente', function () {
    $subscription = radiusSubscription();
    $networkAccount = $subscription->networkAccountLinks()->first()->networkAccount;

    publishEvent('SubscriptionActivated', (string) $subscription->uuid);

    $this->artisan('radius:sync')->assertExitCode(0);

    expect(IspRadiusAccount::where('username', $networkAccount->username)->first()->status)->toBe('active');
});
