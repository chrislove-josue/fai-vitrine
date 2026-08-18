<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Concerns\ResolvesCustomer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ReferenceGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentController
{
    use ResolvesCustomer;

    public function index(): View
    {
        $customer = $this->clientCustomer();

        $payments = $customer !== null
            ? Payment::where('customer_id', $customer->id)->orderByDesc('created_at')->get()
            : collect();

        return view('espace.payments.index', [
            'customer' => $customer,
            'payments' => $payments,
        ]);
    }

    /**
     * Crée une demande de paiement pour une facture du client.
     *
     * Le paiement reste en attente : seule la confirmation signée du
     * prestataire (webhook payment.confirmed) l'acquitte.
     */
    public function store(Invoice $invoice): RedirectResponse
    {
        $customer = $this->clientCustomerOrFail();

        abort_unless($invoice->customer_id === $customer->id, 404);

        if ($invoice->amount_due <= 0 || in_array($invoice->status, [
            Invoice::STATUS_PAID,
            Invoice::STATUS_CANCELLED,
            Invoice::STATUS_REFUNDED,
        ], true)) {
            return back()->with('error', 'Cette facture ne peut plus être payée.');
        }

        $payment = Payment::create([
            'payment_reference' => ReferenceGenerator::paymentReference(),
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'subscription_id' => $invoice->subscription_id,
            'amount' => $invoice->amount_due,
            'currency' => $invoice->currency,
            'method' => 'mobile_money',
            'provider' => config('services.payment.provider', 'mobile_money'),
            'status' => Payment::STATUS_PENDING,
            'metadata' => ['origin' => 'client_portal'],
        ]);

        return redirect()->route('client.payments.index')
            ->with('status', sprintf('Demande de paiement %s créée, en attente de confirmation par le prestataire.', $payment->payment_reference));
    }
}
