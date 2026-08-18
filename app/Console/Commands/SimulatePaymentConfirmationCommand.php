<?php

namespace App\Console\Commands;

use App\Models\ExternalSystem;
use App\Models\Payment;
use App\Services\WebhookService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Confirme un paiement du portail via le flux webhook signé réel.
 *
 * Équivalent de l'envoi d'une confirmation par le prestataire : le payload
 * est signé avec le secret du système externe puis passé à WebhookService,
 * exactement comme l'endpoint HTTP /api/webhooks/incoming.
 */
class SimulatePaymentConfirmationCommand extends Command
{
    protected $signature = 'payment:simulate-confirm {reference : Référence du paiement en attente}
        {--system-code= : Code du système externe (défaut : premier gateway actif)}
        {--transaction-id= : Identifiant transaction côté prestataire (défaut : généré)}';

    protected $description = 'Simule la confirmation d\'un paiement du portail par le prestataire (webhook signé).';

    public function handle(WebhookService $webhooks): int
    {
        $reference = $this->argument('reference');

        $payment = Payment::where('payment_reference', $reference)->first();

        if ($payment === null) {
            $this->error("Paiement {$reference} introuvable.");

            return self::FAILURE;
        }

        if ($payment->status !== Payment::STATUS_PENDING) {
            $this->error("Le paiement {$reference} n'est pas en attente (statut actuel : {$payment->status}).");

            return self::FAILURE;
        }

        $system = $this->option('system-code') !== null && $this->option('system-code') !== ''
            ? ExternalSystem::where('code', $this->option('system-code'))->where('status', 'active')->first()
            : ExternalSystem::where('type', 'payment_gateway')->where('status', 'active')->first();

        if ($system === null) {
            $this->error('Aucun système externe de paiement actif. Créez-en un avec un webhook_secret.');

            return self::FAILURE;
        }

        $secret = data_get($system->configuration, 'webhook_secret');

        if ($secret === null) {
            $this->error("Aucun webhook_secret configuré pour le système {$system->code}.");

            return self::FAILURE;
        }

        $transactionId = $this->option('transaction-id') ?? 'SIM-'.strtoupper(Str::random(12));

        $payload = json_encode([
            'event' => WebhookService::EVENT_PAYMENT_CONFIRMED,
            'external_id' => $transactionId,
            'reference' => $payment->payment_reference,
            'transaction_id' => $transactionId,
            'customer_number' => $payment->customer->customer_number,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'provider' => $payment->provider,
        ], JSON_THROW_ON_ERROR);

        $signature = 'sha256='.hash_hmac('sha256', $payload, $secret);

        $result = $webhooks->receive(
            $system,
            WebhookService::EVENT_PAYMENT_CONFIRMED,
            $transactionId,
            $payload,
            $signature,
        );

        if ($result['status'] !== 'processed') {
            $this->error("Échec de la confirmation ({$result['status']}). Reçu : {$result['receipt']->uuid}");

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Paiement %s confirmé (%s %s) — statut : %s.',
            $payment->payment_reference,
            number_format($payment->amount, 0, ',', ' '),
            $payment->currency,
            $payment->fresh()->status,
        ));

        return self::SUCCESS;
    }
}
