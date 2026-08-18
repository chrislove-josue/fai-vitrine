<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Facturation : émission de factures et application des paiements.
 *
 * La source de vérité commerciale est isp_core. Une facture émise
 * reste immuable (corrections via credit_notes). L'application d'un
 * paiement synchronise facture → abonnement → outbox.
 */
class BillingService
{
    public function __construct(private readonly SubscriptionLifecycleService $lifecycle) {}

    /**
     * Émet une facture pour un abonnement sur la base du prix courant de l'offre.
     */
    public function issueInvoiceForSubscription(Subscription $subscription, ?Carbon $issueDate = null, ?Carbon $dueDate = null): Invoice
    {
        if ($subscription->status === Subscription::STATUS_PENDING) {
            throw new InvalidArgumentException('Impossible de facturer un abonnement en attente d\'activation.');
        }

        $issueDate = $issueDate ?? now();
        $dueDate = $dueDate ?? $issueDate->copy()->addDays(7);

        $price = $subscription->price ?: $subscription->offer->currentPrice()->amount;
        $currency = $subscription->currency;

        return DB::connection('isp_core')->transaction(function () use ($subscription, $issueDate, $dueDate, $price, $currency) {
            $invoice = Invoice::create([
                'invoice_number' => ReferenceGenerator::invoiceNumber(),
                'customer_id' => $subscription->customer_id,
                'subscription_id' => $subscription->id,
                'status' => Invoice::STATUS_ISSUED,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'subtotal' => $price,
                'discount' => 0,
                'tax' => 0,
                'total' => $price,
                'amount_paid' => 0,
                'amount_due' => $price,
                'currency' => $currency,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => sprintf(
                    'Abonnement %s — %s (du %s au %s)',
                    $subscription->offer->name,
                    $subscription->offer->code,
                    $subscription->starts_at?->format('d/m/Y') ?? '-',
                    $subscription->expires_at?->format('d/m/Y') ?? '-',
                ),
                'quantity' => 1,
                'unit_price' => $price,
                'discount' => 0,
                'tax' => 0,
                'total' => $price,
                'reference_type' => 'subscription',
                'reference_id' => $subscription->id,
            ]);

            return $invoice;
        });
    }

    /**
     * Applique un paiement réussi à une facture puis active/réactive l'abonnement.
     *
     * @throws InvalidArgumentException si le paiement est déjà appliqué ou la facture annulée
     */
    public function applyPayment(Payment $payment): Payment
    {
        if ($payment->status === Payment::STATUS_SUCCESSFUL && $payment->paid_at !== null) {
            throw new InvalidArgumentException('Ce paiement a déjà été appliqué.');
        }

        if ($payment->invoice === null) {
            throw new InvalidArgumentException('Le paiement n\'est rattaché à aucune facture.');
        }

        if ($payment->invoice->status === Invoice::STATUS_CANCELLED || $payment->invoice->status === Invoice::STATUS_REFUNDED) {
            throw new InvalidArgumentException('La facture n\'accepte plus de paiement.');
        }

        return DB::connection('isp_core')->transaction(function () use ($payment) {
            $invoice = $payment->invoice;

            $payment->forceFill([
                'status' => Payment::STATUS_SUCCESSFUL,
                'paid_at' => now(),
            ])->save();

            $newAmountPaid = $invoice->amount_paid + $payment->amount;
            $invoice->forceFill([
                'amount_paid' => $newAmountPaid,
                'amount_due' => max(0, $invoice->total - $newAmountPaid),
            ]);

            if ($invoice->amount_due <= 0) {
                $invoice->forceFill(['status' => Invoice::STATUS_PAID, 'paid_at' => now()]);
            } else {
                $invoice->forceFill(['status' => Invoice::STATUS_PARTIALLY_PAID]);
            }
            $invoice->save();

            $subscription = $payment->subscription ?? $invoice->subscription;
            if ($subscription !== null && $subscription->status !== Subscription::STATUS_ACTIVE) {
                $this->lifecycle->reactivate($subscription, source: 'billing', actorType: 'payment', actorId: $payment->id);
            }

            return $payment;
        });
    }
}
