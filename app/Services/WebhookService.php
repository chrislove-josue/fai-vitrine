<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\ExternalSystem;
use App\Models\Payment;
use App\Models\WebhookReceipt;
use Illuminate\Support\Str;

/**
 * Réception et traitement des webhooks signés (paiements, notifications).
 *
 * Sécurité : la signature HMAC-SHA256 du corps brut est vérifiée en temps
 * constant contre le secret configuré du système externe. Les livraisons
 * sont idempotentes (event + external_id) et tracées dans webhook_receipts.
 */
class WebhookService
{
    public const EVENT_PAYMENT_CONFIRMED = 'payment.confirmed';

    /**
     * Vérifie la signature HMAC-SHA256 d'un payload.
     */
    public function verifySignature(ExternalSystem $system, string $payload, string $signature): bool
    {
        $secret = data_get($system->configuration, 'webhook_secret');

        if ($secret === null || $signature === '') {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Réceptionne un webhook signé : vérifie, trace et traite l'événement.
     *
     * @return array{status: string, receipt: WebhookReceipt}
     */
    public function receive(ExternalSystem $system, string $event, ?string $externalId, string $payload, string $signature, ?string $ipAddress = null): array
    {
        if (! $this->verifySignature($system, $payload, $signature)) {
            $receipt = $this->record($system, $event, $externalId, $payload, $signature, 'signature_failed');

            return ['status' => 'signature_failed', 'receipt' => $receipt];
        }

        $existing = WebhookReceipt::where('event', $event)
            ->where('external_id', $externalId)
            ->whereIn('status', ['received', 'processed'])
            ->first();

        if ($existing !== null) {
            return ['status' => 'duplicate', 'receipt' => $existing];
        }

        $receipt = $this->record($system, $event, $externalId, $payload, $signature, 'received', $ipAddress);

        try {
            $this->process($event, $payload);
            $receipt->update(['status' => 'processed', 'processed_at' => now(), 'error_message' => null]);

            return ['status' => 'processed', 'receipt' => $receipt];
        } catch (\Throwable $e) {
            $receipt->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

            throw $e;
        }
    }

    private function record(ExternalSystem $system, string $event, ?string $externalId, string $payload, string $signature, string $status, ?string $ipAddress = null): WebhookReceipt
    {
        return WebhookReceipt::create([
            'external_system_id' => $system->id,
            'event' => $event,
            'external_id' => $externalId,
            'payload' => $payload,
            'signature' => $signature,
            'status' => $status,
        ]);
    }

    /**
     * Traite un événement webhook connu (ex. confirmation de paiement).
     */
    public function process(string $event, string $payload): void
    {
        $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        match ($event) {
            self::EVENT_PAYMENT_CONFIRMED => $this->confirmPayment($data),
            default => throw new \RuntimeException("Événement webhook non géré : {$event}"),
        };
    }

    private function confirmPayment(array $data): void
    {
        $reference = $data['reference'] ?? null;
        $externalId = $data['transaction_id'] ?? null;
        $amount = (float) ($data['amount'] ?? 0);
        $currency = $data['currency'] ?? 'XOF';

        if ($reference === null || $externalId === null) {
            throw new \RuntimeException('Webhook de paiement incomplet (reference / transaction_id requis).');
        }

        $customer = Customer::where('customer_number', $data['customer_number'] ?? null)->first();

        if ($customer === null) {
            throw new \RuntimeException('Client introuvable pour le webhook de paiement.');
        }

        $duplicate = Payment::where('provider_reference', $externalId)->first();

        if ($duplicate !== null) {
            return;
        }

        $portalPayment = Payment::where('payment_reference', $reference)
            ->where('status', Payment::STATUS_PENDING)
            ->first();

        if ($portalPayment !== null) {
            $portalPayment->forceFill([
                'transaction_id' => (string) $externalId,
                'provider_reference' => (string) $externalId,
                'provider' => $data['provider'] ?? $portalPayment->provider,
                'metadata' => array_merge($portalPayment->metadata ?? [], ['webhook' => true]),
            ])->save();

            app(BillingService::class)->applyPayment($portalPayment);

            return;
        }

        Payment::create([
            'payment_reference' => 'PAY-'.Str::upper(Str::random(10)),
            'customer_id' => $customer->id,
            'amount' => $amount,
            'currency' => $currency,
            'method' => 'mobile_money',
            'provider' => $data['provider'] ?? 'mobile_money',
            'status' => 'pending',
            'transaction_id' => (string) $externalId,
            'provider_reference' => (string) $externalId,
            'metadata' => ['webhook' => true],
        ]);
    }

    public function audit(string $action, string $ipAddress, string $metadata): void
    {
        AuditLog::create([
            'action' => $action,
            'ip_address' => $ipAddress,
            'metadata' => ['event' => $metadata],
        ]);
    }
}
