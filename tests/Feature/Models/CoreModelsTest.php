<?php

use App\Models\Customer;
use App\Models\NetworkProfile;
use App\Models\Offer;
use App\Models\OfferPrice;
use App\Models\Subscription;
use Illuminate\Database\QueryException;

test('un client individuel affiche son nom complet', function () {
    $customer = new Customer([
        'type' => 'individual',
        'first_name' => 'Jean',
        'last_name' => 'Kouassi',
    ]);

    expect($customer->display_name)->toBe('Jean Kouassi');
});

test('un client entreprise affiche le nom de la société', function () {
    $customer = new Customer([
        'type' => 'company',
        'company_name' => 'Jenys SAS',
        'first_name' => null,
    ]);

    expect($customer->display_name)->toBe('Jenys SAS');
});

test('un customer_number doit être unique', function () {
    $customer = Customer::factory()->create();

    expect(fn () => Customer::factory()->create(['customer_number' => $customer->customer_number]))
        ->toThrow(QueryException::class);
});

test('les relations du client fonctionnent', function () {
    $customer = Customer::factory()->create();
    $customer->contacts()->create(['type' => 'email', 'value' => 'a@b.c']);
    $customer->addresses()->create(['type' => 'billing', 'city' => 'Abidjan']);
    $customer->subscriptions()->create(Subscription::factory()->make()->toArray());

    expect($customer->contacts()->count())->toBe(1);
    expect($customer->addresses()->count())->toBe(1);
    expect($customer->subscriptions()->count())->toBe(1);
});

test('une offre pointe vers un profil réseau', function () {
    $profile = NetworkProfile::factory()->create(['code' => 'FIBRE20', 'download_speed' => 20_000_000]);
    $offer = Offer::factory()->create(['network_profile_id' => $profile->id]);

    expect($offer->networkProfile->code)->toBe('FIBRE20');
});

test('le prix courant d\'une offre respecte l\'historique des prix', function () {
    $offer = Offer::factory()->create();

    OfferPrice::create(['offer_id' => $offer->id, 'amount' => 10_000, 'currency' => 'XOF', 'starts_at' => now()->subMonths(6), 'ends_at' => now()->subMonth()]);
    $current = OfferPrice::create(['offer_id' => $offer->id, 'amount' => 12_000, 'currency' => 'XOF', 'starts_at' => now()->subMonth()]);

    expect($offer->currentPrice()->amount)->toBe(12_000);
});

test('un abonnement actif possède les dates du cycle', function () {
    $subscription = Subscription::factory()->active()->create();

    expect($subscription->status)->toBe('active');
    expect($subscription->starts_at)->not->toBeNull();
    expect($subscription->expires_at)->not->toBeNull();
    expect($subscription->next_renewal_at)->not->toBeNull();
    expect($subscription->uuid)->toBeUuid();
});

test('le numéro d\'abonnement est unique', function () {
    $subscription = Subscription::factory()->create();

    expect(fn () => Subscription::factory()->create(['subscription_number' => $subscription->subscription_number]))
        ->toThrow(QueryException::class);
});
