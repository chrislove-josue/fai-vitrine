<?php

use App\Models\Customer;
use App\Models\IspRadiusAccount;
use App\Models\NetworkAccount;
use App\Models\OutboxEvent;
use App\Models\RadCheck;
use App\Models\Subscription;
use App\Models\SubscriptionNetworkAccount;
use App\Models\SyncOperation;
use App\Services\OutboxService;
use App\Services\RadiusSyncService;

test('la suspension d\'un compte n\'expose jamais le mot de passe', function () {
    $customer = Customer::factory()->create();
    $networkAccount = NetworkAccount::factory()->create(['customer_id' => $customer->id, 'username' => 'secure-user-1']);
    $subscription = Subscription::factory()->create(['customer_id' => $customer->id]);
    SubscriptionNetworkAccount::create([
        'subscription_id' => $subscription->id,
        'network_account_id' => $networkAccount->id,
        'status' => 'active',
    ]);

    app(OutboxService::class)->publish('SubscriptionActivated', 'subscription', (string) $subscription->uuid);
    app(OutboxService::class)->publish('SubscriptionSuspended', 'subscription', (string) $subscription->uuid);

    $result = app(RadiusSyncService::class)->processPending();

    expect($result['failed'])->toBe(0);

    $password = RadCheck::where('username', 'secure-user-1')->where('attribute', 'Cleartext-Password')->value('value');
    expect($password)->not->toBeNull();
    expect($password)->not->toBeEmpty();

    $reject = RadCheck::where('username', 'secure-user-1')->where('attribute', 'Auth-Type')->value('value');
    expect($reject)->toBe('Reject');
});

test('la résiliation purge totalement les credentials du NAS', function () {
    $customer = Customer::factory()->create();
    $networkAccount = NetworkAccount::factory()->create(['customer_id' => $customer->id, 'username' => 'purge-user-1']);
    $subscription = Subscription::factory()->create(['customer_id' => $customer->id]);
    SubscriptionNetworkAccount::create([
        'subscription_id' => $subscription->id,
        'network_account_id' => $networkAccount->id,
        'status' => 'active',
    ]);

    app(OutboxService::class)->publish('SubscriptionActivated', 'subscription', (string) $subscription->uuid);
    app(OutboxService::class)->publish('SubscriptionTerminated', 'subscription', (string) $subscription->uuid);

    app(RadiusSyncService::class)->processPending();

    expect(RadCheck::where('username', 'purge-user-1')->count())->toBe(0);
    expect(RadCheck::where('attribute', 'Cleartext-Password')->where('value', 'like', '%purge-user-1%')->count())->toBe(0);
});

test('un abonnement inexistant est tracé en échec sans écrire dans freeradius', function () {
    app(OutboxService::class)->publish('SubscriptionActivated', 'subscription', 'uuid-inexistante');
    app(OutboxService::class)->publish('SubscriptionTerminated', 'subscription', 'uuid-inexistante');

    $result = app(RadiusSyncService::class)->processPending();

    expect($result['processed'])->toBe(0);
    expect($result['failed'])->toBe(2);
    expect(SyncOperation::where('status', 'failed')->count())->toBe(2);
    expect(OutboxEvent::where('status', 'failed')->count())->toBe(2);
    expect(IspRadiusAccount::count())->toBe(0);
});

test('les événements en échec ne restent jamais en processing (crash relancés)', function () {
    app(OutboxService::class)->publish('SubscriptionActivated', 'subscription', 'uuid-inexistante');

    app(RadiusSyncService::class)->processPending();

    expect(OutboxEvent::where('status', 'processing')->count())->toBe(0);

    $requeued = app(OutboxService::class)->requeueStaleEvents();
    expect($requeued)->toBe(0);
});

test('le mot de passe radcheck est aléatoire et ne provient pas des données métier', function () {
    $customer = Customer::factory()->create();
    $networkAccount = NetworkAccount::factory()->create(['customer_id' => $customer->id, 'username' => 'random-pass-user']);
    $subscription = Subscription::factory()->create(['customer_id' => $customer->id]);
    SubscriptionNetworkAccount::create([
        'subscription_id' => $subscription->id,
        'network_account_id' => $networkAccount->id,
        'status' => 'active',
    ]);

    app(OutboxService::class)->publish('SubscriptionActivated', 'subscription', (string) $subscription->uuid);
    app(RadiusSyncService::class)->processPending();

    $value = RadCheck::where('username', 'random-pass-user')->where('attribute', 'Cleartext-Password')->value('value');

    expect($value)->not->toBe($networkAccount->username);
    expect($value)->toHaveLength(16);
});
