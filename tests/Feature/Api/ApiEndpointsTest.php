<?php

use App\Models\Customer;
use App\Services\ApiClientService;

function apiCredentials(): array
{
    return app(ApiClientService::class)->createClient('Client de test');
}

test('l\'endpoint /api/health est public', function () {
    $this->getJson('/api/health')->assertOk()->assertJson(['status' => 'ok']);
});

test('l\'endpoint /api/v1/customers exige une authentification', function () {
    $this->getJson('/api/v1/customers')->assertUnauthorized();
});

test('l\'endpoint /api/v1/customers rejette des identifiants incorrects', function () {
    $this->withHeaders([
        'X-Client-Id' => 'cli_faux',
        'X-Client-Secret' => 'mauvais-secret',
    ])->getJson('/api/v1/customers')->assertUnauthorized();
});

test('l\'endpoint /api/v1/customers renvoie les clients avec des identifiants valides', function () {
    $credentials = apiCredentials();
    $customer = Customer::factory()->create(['first_name' => 'Alice', 'last_name' => 'Dupont']);

    $response = $this->withHeaders([
        'X-Client-Id' => $credentials['client']->client_id,
        'X-Client-Secret' => $credentials['secret'],
    ])->getJson('/api/v1/customers')->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.customer_number'))->toBe($customer->customer_number);
    expect($response->json('data.0'))->toHaveKey('email');
});

test('la recherche de clients filtre par nom', function () {
    $credentials = apiCredentials();
    Customer::factory()->create(['first_name' => 'Alice', 'last_name' => 'Dupont', 'email' => 'alice@example.com']);
    Customer::factory()->create(['first_name' => 'Bob', 'last_name' => 'Martin', 'email' => 'bob@example.com']);

    $response = $this->withHeaders([
        'X-Client-Id' => $credentials['client']->client_id,
        'X-Client-Secret' => $credentials['secret'],
    ])->getJson('/api/v1/customers?q=alice@example.com')->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.first_name'))->toBe('Alice');
});

test('le rate limiting protège les endpoints authentifiés', function () {
    $credentials = apiCredentials();

    $headers = [
        'X-Client-Id' => $credentials['client']->client_id,
        'X-Client-Secret' => $credentials['secret'],
    ];

    for ($i = 0; $i < 10; $i++) {
        $this->withHeaders($headers)->getJson('/api/v1/customers')->assertOk();
    }

    $this->withHeaders($headers)->getJson('/api/v1/customers')
        ->assertStatus(429);
});
