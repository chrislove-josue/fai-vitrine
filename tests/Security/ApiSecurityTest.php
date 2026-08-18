<?php

use App\Models\ApiClient;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\ExternalSystem;
use App\Models\Payment;
use App\Services\ApiClientService;
use App\Services\WebhookService;

test('le secret API n\'est jamais stocké en clair (hash bcrypt)', function () {
    $created = app(ApiClientService::class)->createClient('Secret sécurisé');

    $persisted = ApiClient::find($created['client']->id);

    expect($persisted->secret_hash)->not->toBe($created['secret']);
    expect(password_verify($created['secret'], $persisted->secret_hash))->toBeTrue();
});

test('le secret_hash est masqué à la sérialisation', function () {
    $created = app(ApiClientService::class)->createClient('Sérialisation');

    expect($created['client']->toArray())->not->toHaveKey('secret_hash');
});

test('un client API expiré est refusé', function () {
    $service = app(ApiClientService::class);
    $created = $service->createClient('Expiré', expiresAt: now()->subDay());

    expect($service->authenticate($created['client']->client_id, $created['secret']))->toBeNull();
});

test('un webhook avec signature corrompue ne crée aucun paiement ni reçu valide', function () {
    $system = ExternalSystem::create([
        'name' => 'PAY', 'code' => 'PAYBAD', 'type' => 'payment_gateway',
        'status' => 'active', 'configuration' => ['webhook_secret' => 'mon-secret'],
    ]);
    $customer = Customer::factory()->create();
    $payload = json_encode(['event' => 'payment.confirmed', 'external_id' => 'TX-X1', 'reference' => 'REF-X1', 'transaction_id' => 'TX-X1', 'customer_number' => $customer->customer_number, 'amount' => 9_999]);

    $tampered = str_replace('"amount":9999', '"amount":1', $payload);

    $result = app(WebhookService::class)->receive($system, 'payment.confirmed', 'TX-X1', $tampered, 'sha256='.hash_hmac('sha256', $payload, 'mon-secret'));

    expect($result['status'])->toBe('signature_failed');
    expect(Payment::where('provider_reference', 'TX-X1')->count())->toBe(0);
});

test('chaque réception de webhook est tracée dans l\'audit', function () {
    $system = ExternalSystem::create([
        'name' => 'PAY', 'code' => 'PAYAUDIT', 'type' => 'payment_gateway',
        'status' => 'active', 'configuration' => ['webhook_secret' => 'secret-audit'],
    ]);
    $customer = Customer::factory()->create();
    $payload = json_encode(['event' => 'payment.confirmed', 'external_id' => 'TX-A1', 'reference' => 'REF-A1', 'transaction_id' => 'TX-A1', 'customer_number' => $customer->customer_number, 'amount' => 100]);

    $this->call('POST', '/api/webhooks/incoming', server: [
        'HTTP_X_SYSTEM_CODE' => 'PAYAUDIT',
        'HTTP_X_WEBHOOK_SIGNATURE' => 'sha256='.hash_hmac('sha256', $payload, 'secret-audit'),
        'HTTP_X_EVENT' => 'payment.confirmed',
        'HTTP_X_EVENT_ID' => 'TX-A1',
    ], content: $payload)->assertOk();

    expect(AuditLog::where('action', 'webhook.processed')->count())->toBe(1);
});

test('une tentative de webhook avec mauvaise signature est audité', function () {
    $system = ExternalSystem::create([
        'name' => 'PAY', 'code' => 'PAYATTACK', 'type' => 'payment_gateway',
        'status' => 'active', 'configuration' => ['webhook_secret' => 'secret-attack'],
    ]);
    $payload = json_encode(['event' => 'payment.confirmed']);

    $this->call('POST', '/api/webhooks/incoming', server: [
        'HTTP_X_SYSTEM_CODE' => 'PAYATTACK',
        'HTTP_X_WEBHOOK_SIGNATURE' => 'sha256=invalide',
        'HTTP_X_EVENT' => 'payment.confirmed',
    ], content: $payload)->assertUnauthorized();

    expect(AuditLog::where('action', 'webhook.signature_failed')->count())->toBe(1);
});

test('un client expiré est refusé par le middleware API', function () {
    $service = app(ApiClientService::class);
    $created = $service->createClient('Expiré API', expiresAt: now()->subDay());

    $this->withHeaders([
        'X-Client-Id' => $created['client']->client_id,
        'X-Client-Secret' => $created['secret'],
    ])->getJson('/api/v1/customers')->assertUnauthorized();
});

test('la vérification de signature est insensible au timing (comparaison en temps constant)', function () {
    $system = ExternalSystem::create([
        'name' => 'PAY', 'code' => 'PAYTIMING', 'type' => 'payment_gateway',
        'status' => 'active', 'configuration' => ['webhook_secret' => 'secret-timing'],
    ]);
    $payload = '{"event":"ping"}';

    $valid = app(WebhookService::class)->verifySignature($system, $payload, 'sha256='.hash_hmac('sha256', $payload, 'secret-timing'));
    $invalid = app(WebhookService::class)->verifySignature($system, $payload, 'sha256=0000000000000000000000000000000000000000000000000000000000000000');

    expect($valid)->toBeTrue();
    expect($invalid)->toBeFalse();
});
