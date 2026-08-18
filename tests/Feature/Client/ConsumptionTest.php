<?php

use App\Models\Customer;
use App\Models\NetworkAccount;
use App\Models\NetworkSession;

test('la consommation liste les sessions du client et résume les données', function () {
    $customer = Customer::factory()->create();
    $account = NetworkAccount::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'active',
    ]);
    NetworkSession::create([
        'network_account_uuid' => $account->uuid,
        'username' => $account->username,
        'session_id' => 'SES-OWN-1',
        'ip_address' => '192.168.1.10',
        'started_at' => now()->subHours(2),
        'ended_at' => now()->subHour(),
        'session_seconds' => 3600,
        'bytes_in' => 50 * 1024 * 1024,
        'bytes_out' => 5 * 1024 * 1024,
        'terminate_cause' => 'User-Request',
    ]);
    $user = clientPortalUser($customer);

    $this->actingAs($user)->get(route('client.sessions.index'))
        ->assertOk()
        ->assertSee('192.168.1.10')
        ->assertSee('50,0 Mo')
        ->assertSee('Sessions réseau');
});

test('les sessions d\'un autre client ne sont pas affichées', function () {
    $customer = Customer::factory()->create();
    $other = Customer::factory()->create();
    $otherAccount = NetworkAccount::factory()->create(['customer_id' => $other->id]);
    NetworkSession::create([
        'network_account_uuid' => $otherAccount->uuid,
        'username' => $otherAccount->username,
        'session_id' => 'SES-OTHER-1',
        'started_at' => now()->subHours(2),
        'ended_at' => null,
        'session_seconds' => 7200,
        'bytes_in' => 999 * 1024 * 1024,
        'bytes_out' => 999 * 1024 * 1024,
        'terminate_cause' => null,
    ]);
    $user = clientPortalUser($customer);

    $this->actingAs($user)->get(route('client.sessions.index'))
        ->assertOk()
        ->assertDontSee('SES-OTHER-1');
});

test('un client sans compte ne voit aucune session', function () {
    $user = clientPortalUser(Customer::factory()->create());
    $user->update(['customer_uuid' => null]);

    $this->actingAs($user)->get(route('client.sessions.index'))
        ->assertOk()
        ->assertSee('Aucun compte client');
});
