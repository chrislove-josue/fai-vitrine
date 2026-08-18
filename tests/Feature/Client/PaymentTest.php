<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\ReferenceGenerator;
use App\Services\WebhookService;

function payableInvoice(Customer $customer, Subscription $subscription, string $status = 'issued'): Invoice
{
    return Invoice::factory()->create([
        'customer_id' => $customer->id,
        'subscription_id' => $subscription->id,
        'status' => $status,
        'issue_date' => now()->subDays(3),
        'due_date' => now()->addDays(4),
        'subtotal' => 15_000,
        'total' => 15_000,
        'amount_paid' => 0,
        'amount_due' => 15_000,
    ]);
}

test('payer une facture crée une demande de paiement en attente', function () {
    $customer = Customer::factory()->create();
    $subscription = Subscription::factory()->active()->create(['customer_id' => $customer->id]);
    $invoice = payableInvoice($customer, $subscription);
    $user = clientPortalUser($customer);

    $this->actingAs($user)->post(route('client.payments.store', $invoice))
        ->assertRedirect(route('client.payments.index'));

    $payment = Payment::where('customer_id', $customer->id)->first();
    expect($payment)->not->toBeNull();
    expect($payment->status)->toBe(Payment::STATUS_PENDING);
    expect($payment->invoice_id)->toBe($invoice->id);
    expect($payment->subscription_id)->toBe($subscription->id);
    expect($payment->amount)->toBe(15_000);
    expect($payment->payment_reference)->toStartWith('PAY-');
});

test('on ne peut pas payer la facture d\'un autre client', function () {
    $customer = Customer::factory()->create();
    $other = Customer::factory()->create();
    $subscription = Subscription::factory()->active()->create(['customer_id' => $other->id]);
    $invoice = payableInvoice($other, $subscription);
    $user = clientPortalUser($customer);

    $this->actingAs($user)->post(route('client.payments.store', $invoice))->assertNotFound();

    expect(Payment::where('invoice_id', $invoice->id)->count())->toBe(0);
});

test('on ne peut pas payer une facture déjà payée', function () {
    $customer = Customer::factory()->create();
    $subscription = Subscription::factory()->active()->create(['customer_id' => $customer->id]);
    $invoice = payableInvoice($customer, $subscription, 'paid');
    $user = clientPortalUser($customer);

    $this->actingAs($user)->post(route('client.payments.store', $invoice))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Payment::where('customer_id', $customer->id)->count())->toBe(0);
});

test('la confirmation webhook acquitte le paiement, la facture et réactive l\'abonnement', function () {
    $gateway = paymentGatewaySystem();
    $customer = Customer::factory()->create();
    $subscription = Subscription::factory()->create([
        'customer_id' => $customer->id,
        'status' => Subscription::STATUS_SUSPENDED,
        'suspended_at' => now(),
    ]);
    $invoice = payableInvoice($customer, $subscription);
    $user = clientPortalUser($customer);

    $this->actingAs($user)->post(route('client.payments.store', $invoice));
    $payment = Payment::where('customer_id', $customer->id)->firstOrFail();

    $payload = json_encode([
        'event' => WebhookService::EVENT_PAYMENT_CONFIRMED,
        'external_id' => 'TX-FLOW-1',
        'reference' => $payment->payment_reference,
        'transaction_id' => 'TX-FLOW-1',
        'customer_number' => $customer->customer_number,
        'amount' => $payment->amount,
        'currency' => $payment->currency,
    ]);

    $result = app(WebhookService::class)->receive(
        $gateway,
        WebhookService::EVENT_PAYMENT_CONFIRMED,
        'TX-FLOW-1',
        $payload,
        signPayloadFor($payload, 'secret-webhook-tres-long'),
    );

    expect($result['status'])->toBe('processed');

    expect($payment->fresh()->status)->toBe(Payment::STATUS_SUCCESSFUL);
    expect($payment->fresh()->provider_reference)->toBe('TX-FLOW-1');
    expect($invoice->fresh()->status)->toBe(Invoice::STATUS_PAID);
    expect($invoice->fresh()->amount_due)->toBe(0);
    expect($subscription->fresh()->status)->toBe(Subscription::STATUS_ACTIVE);
});

test('la liste des paiements affiche les demandes du client', function () {
    $customer = Customer::factory()->create();
    $subscription = Subscription::factory()->active()->create(['customer_id' => $customer->id]);
    $invoice = payableInvoice($customer, $subscription);
    $user = clientPortalUser($customer);

    $this->actingAs($user)->post(route('client.payments.store', $invoice));

    $payment = Payment::where('customer_id', $customer->id)->firstOrFail();

    $this->actingAs($user)->get(route('client.payments.index'))
        ->assertOk()
        ->assertSee($payment->payment_reference);
});

test('la commande payment:simulate-confirm confirme un paiement via le flux signé', function () {
    paymentGatewaySystem();
    $customer = Customer::factory()->create();
    $subscription = Subscription::factory()->create([
        'customer_id' => $customer->id,
        'status' => Subscription::STATUS_SUSPENDED,
    ]);
    $invoice = payableInvoice($customer, $subscription);

    $payment = Payment::create([
        'payment_reference' => ReferenceGenerator::paymentReference(),
        'customer_id' => $customer->id,
        'invoice_id' => $invoice->id,
        'subscription_id' => $subscription->id,
        'amount' => $invoice->amount_due,
        'currency' => $invoice->currency,
        'method' => 'mobile_money',
        'provider' => 'mobile_money',
        'status' => Payment::STATUS_PENDING,
    ]);

    $this->artisan('payment:simulate-confirm', ['reference' => $payment->payment_reference])
        ->assertSuccessful();

    expect($payment->fresh()->status)->toBe(Payment::STATUS_SUCCESSFUL);
    expect($invoice->fresh()->status)->toBe(Invoice::STATUS_PAID);
});

test('la commande payment:simulate-confirm refuse une référence inconnue', function () {
    $this->artisan('payment:simulate-confirm', ['reference' => 'PAY-INCONNUE'])->assertFailed();
});
