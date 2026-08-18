<?php

use App\Models\ApiClient;
use App\Services\ApiClientService;

test('la création d\'un client API génère identifiant et secret distincts', function () {
    $result = app(ApiClientService::class)->createClient('Système de paiement');

    expect($result['client'])->toBeInstanceOf(ApiClient::class);
    expect($result['client']->status)->toBe('active');
    expect($result['client']->client_id)->toStartWith('cli_');
    expect(strlen($result['secret']))->toBe(48);
    expect($result['secret'])->not->toBe($result['client']->secret_hash);
    expect($result['client']->secret_hash)->not->toBe($result['secret']);
});

test('l\'authentification accepte les identifiants valides', function () {
    $service = app(ApiClientService::class);
    $created = $service->createClient('Valide');

    $client = $service->authenticate($created['client']->client_id, $created['secret']);

    expect($client)->not->toBeNull();
    expect($client->id)->toBe($created['client']->id);
    expect($client->last_used_at)->not->toBeNull();
});

test('l\'authentification rejette un secret incorrect', function () {
    $service = app(ApiClientService::class);
    $created = $service->createClient('Mauvais secret');

    expect($service->authenticate($created['client']->client_id, 'mauvais-secret'))->toBeNull();
});

test('l\'authentification rejette un identifiant inconnu', function () {
    expect(app(ApiClientService::class)->authenticate('cli_inconnu', 'secret'))->toBeNull();
});

test('l\'authentification rejette un client révoqué', function () {
    $service = app(ApiClientService::class);
    $created = $service->createClient('Révoqué');
    $service->revoke($created['client']);

    expect($service->authenticate($created['client']->client_id, $created['secret']))->toBeNull();
});
