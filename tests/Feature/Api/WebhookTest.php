<?php

use App\Models\Customer;
use App\Models\ExternalSystem;
use App\Models\Payment;
use App\Models\WebhookReceipt;
use App\Services\WebhookService;

function signedWebhookSystem(): ExternalSystem
{
    return ExternalSystem::create([
        'name' => 'Passerelle de paiement',
        'code' => 'PAYGW',
        'type' => 'payment_gateway',
        'base_url' => 'https://pay.example.com',
        'status' => 'active',
        'configuration' => ['webhook_secret' => 'secret-webhook-tres-long'],
    ]);
}

function signPayload(string $payload, string $secret): string
{
    return 'sha256='.hash_hmac('sha256', $payload, $secret);
}

test('un webhook signé valide est traité et trace un reçu', function () {
    $system = signedWebhookSystem();
    $customer = Customer::factory()->create();
    $payload = json_encode([
        'event' => 'payment.confirmed',
        'external_id' => 'TX-100',
        'reference' => 'REF-100',
        'transaction_id' => 'TX-100',
        'customer_number' => $customer->customer_number,
        'amount' => 15_000,
        'currency' => 'XOF',
    ]);

    $result = app(WebhookService::class)->receive(
        $system,
        'payment.confirmed',
        'TX-100',
        $payload,
        signPayload($payload, 'secret-webhook-tres-long'),
    );

    expect($result['status'])->toBe('processed');
    expect($result['receipt'])->toBeInstanceOf(WebhookReceipt::class);
    expect($result['receipt']->status)->toBe('processed');

    expect(Payment::where('provider_reference', 'TX-100')->count())->toBe(1);
    expect(Payment::where('provider_reference', 'TX-100')->first()->amount)->toBe(15000);
});

test('une signature invalide est rejetée sans créer de paiement', function () {
    $system = signedWebhookSystem();
    $customer = Customer::factory()->create();
    $payload = json_encode(['event' => 'payment.confirmed', 'external_id' => 'TX-200', 'reference' => 'REF-200', 'transaction_id' => 'TX-200', 'customer_number' => $customer->customer_number, 'amount' => 100]);

    $result = app(WebhookService::class)->receive($system, 'payment.confirmed', 'TX-200', $payload, 'sha256=signature-fausse');

    expect($result['status'])->toBe('signature_failed');
    expect($result['receipt']->status)->toBe('signature_failed');
    expect(Payment::where('provider_reference', 'TX-200')->count())->toBe(0);
});

test('une livraison en double est idempotente', function () {
    $system = signedWebhookSystem();
    $customer = Customer::factory()->create();
    $payload = json_encode(['event' => 'payment.confirmed', 'external_id' => 'TX-300', 'reference' => 'REF-300', 'transaction_id' => 'TX-300', 'customer_number' => $customer->customer_number, 'amount' => 100]);
    $signature = signPayload($payload, 'secret-webhook-tres-long');

    app(WebhookService::class)->receive($system, 'payment.confirmed', 'TX-300', $payload, $signature);
    $second = app(WebhookService::class)->receive($system, 'payment.confirmed', 'TX-300', $payload, $signature);

    expect($second['status'])->toBe('duplicate');
    expect(Payment::where('provider_reference', 'TX-300')->count())->toBe(1);
});

test('l\'endpoint webhook HTTP accepte un webhook signé', function () {
    $system = signedWebhookSystem();
    $customer = Customer::factory()->create();
    $payload = json_encode(['event' => 'payment.confirmed', 'external_id' => 'TX-400', 'reference' => 'REF-400', 'transaction_id' => 'TX-400', 'customer_number' => $customer->customer_number, 'amount' => 5_000, 'currency' => 'XOF']);

    $this->call('POST', '/api/webhooks/incoming', server: [
        'HTTP_X_SYSTEM_CODE' => 'PAYGW',
        'HTTP_X_WEBHOOK_SIGNATURE' => signPayload($payload, 'secret-webhook-tres-long'),
        'HTTP_X_EVENT' => 'payment.confirmed',
        'HTTP_X_EVENT_ID' => 'TX-400',
    ], content: $payload)->assertOk();

    expect(Payment::where('provider_reference', 'TX-400')->count())->toBe(1);
});

test('l\'endpoint webhook HTTP rejette une signature invalide', function () {
    $system = signedWebhookSystem();
    $payload = json_encode(['event' => 'payment.confirmed', 'external_id' => 'TX-500', 'reference' => 'REF-500', 'transaction_id' => 'TX-500']);

    $this->call('POST', '/api/webhooks/incoming', server: [
        'HTTP_X_SYSTEM_CODE' => 'PAYGW',
        'HTTP_X_WEBHOOK_SIGNATURE' => 'sha256=fausse',
        'HTTP_X_EVENT' => 'payment.confirmed',
        'HTTP_X_EVENT_ID' => 'TX-500',
    ], content: $payload)->assertUnauthorized();

    expect(Payment::count())->toBe(0);
});

test('l\'endpoint webhook HTTP refuse un système inconnu', function () {
    $payload = json_encode(['event' => 'payment.confirmed']);

    $this->call('POST', '/api/webhooks/incoming', server: [
        'HTTP_X_SYSTEM_CODE' => 'INCONNU',
        'HTTP_X_WEBHOOK_SIGNATURE' => 'sha256=abc',
        'HTTP_X_EVENT' => 'payment.confirmed',
    ], content: $payload)->assertUnauthorized();
});
