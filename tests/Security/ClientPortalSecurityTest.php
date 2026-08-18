<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\WebhookService;

function clientSecuredInvoice(Customer $customer, Subscription $subscription): Invoice
{
    return Invoice::factory()->create([
        'customer_id' => $customer->id,
        'subscription_id' => $subscription->id,
        'status' => Invoice::STATUS_ISSUED,
        'subtotal' => 15_000,
        'total' => 15_000,
        'amount_paid' => 0,
        'amount_due' => 15_000,
    ]);
}

test('les pages de l\'espace client exigent une authentification', function () {
    $this->get(route('client.invoices.index'))->assertRedirect(route('login'));
    $this->get(route('client.payments.index'))->assertRedirect(route('login'));
    $this->get(route('client.sessions.index'))->assertRedirect(route('login'));
    $this->get(route('client.profile.show'))->assertRedirect(route('login'));
});

test('un membre du personnel ne peut pas accéder à l\'espace client', function () {
    $staff = User::factory()->create(['status' => 'active']);
    $staff->assignRole('support');
    $customer = Customer::factory()->create();

    $this->actingAs($staff)->get(route('client.invoices.index'))->assertForbidden();
    $this->actingAs($staff)->get(route('client.payments.index'))->assertForbidden();
    $this->actingAs($staff)->get(route('client.sessions.index'))->assertForbidden();
    $this->actingAs($staff)->get(route('client.profile.show'))->assertForbidden();
    $this->actingAs($staff)->get(route('dashboard.index'))->assertForbidden();

    $invoice = clientSecuredInvoice($customer, Subscription::factory()->active()->create(['customer_id' => $customer->id]));
    $this->actingAs($staff)->get(route('client.invoices.show', $invoice))->assertForbidden();
    $this->actingAs($staff)->post(route('client.payments.store', $invoice))->assertForbidden();
});

test('un client ne peut pas lire ou payer les ressources d\'un autre client', function () {
    $customer = Customer::factory()->create();
    $other = Customer::factory()->create();
    $subscription = Subscription::factory()->active()->create(['customer_id' => $other->id]);
    $invoice = clientSecuredInvoice($other, $subscription);
    $user = clientPortalUser($customer);

    $this->actingAs($user)->get(route('client.invoices.show', $invoice))->assertNotFound();
    $this->actingAs($user)->get(route('client.invoices.pdf', $invoice))->assertNotFound();
    $this->actingAs($user)->post(route('client.payments.store', $invoice))->assertNotFound();

    expect(Payment::where('customer_id', $customer->id)->count())->toBe(0);
});

test('le montant d\'une demande de paiement est toujours le restant dû', function () {
    $customer = Customer::factory()->create();
    $subscription = Subscription::factory()->active()->create(['customer_id' => $customer->id]);
    $invoice = clientSecuredInvoice($customer, $subscription);
    $user = clientPortalUser($customer);

    $this->actingAs($user)->post(route('client.payments.store', $invoice), ['amount' => 1])
        ->assertRedirect();

    $payment = Payment::where('customer_id', $customer->id)->firstOrFail();
    expect($payment->amount)->toBe($invoice->amount_due);
    expect($payment->amount)->not->toBe(1);
});

test('une confirmation webhook ne peut pas être rejouée', function () {
    $gateway = paymentGatewaySystem();
    $customer = Customer::factory()->create();
    $subscription = Subscription::factory()->create([
        'customer_id' => $customer->id,
        'status' => Subscription::STATUS_SUSPENDED,
    ]);
    $invoice = clientSecuredInvoice($customer, $subscription);
    $user = clientPortalUser($customer);

    $this->actingAs($user)->post(route('client.payments.store', $invoice));
    $payment = Payment::where('customer_id', $customer->id)->firstOrFail();

    $payload = json_encode([
        'event' => WebhookService::EVENT_PAYMENT_CONFIRMED,
        'external_id' => 'TX-REPLAY',
        'reference' => $payment->payment_reference,
        'transaction_id' => 'TX-REPLAY',
        'customer_number' => $customer->customer_number,
        'amount' => $payment->amount,
        'currency' => $payment->currency,
    ]);
    $signature = signPayloadFor($payload, 'secret-webhook-tres-long');

    $first = app(WebhookService::class)->receive($gateway, WebhookService::EVENT_PAYMENT_CONFIRMED, 'TX-REPLAY', $payload, $signature);
    $second = app(WebhookService::class)->receive($gateway, WebhookService::EVENT_PAYMENT_CONFIRMED, 'TX-REPLAY', $payload, $signature);

    expect($first['status'])->toBe('processed');
    expect($second['status'])->toBe('duplicate');

    expect(Payment::where('payment_reference', $payment->payment_reference)->count())->toBe(1);
    expect($payment->fresh()->status)->toBe(Payment::STATUS_SUCCESSFUL);
    expect($invoice->fresh()->status)->toBe(Invoice::STATUS_PAID);
});

test('une confirmation webhook non signée n\'acquitte pas le paiement', function () {
    $gateway = paymentGatewaySystem();
    $customer = Customer::factory()->create();
    $subscription = Subscription::factory()->create([
        'customer_id' => $customer->id,
        'status' => Subscription::STATUS_SUSPENDED,
    ]);
    $invoice = clientSecuredInvoice($customer, $subscription);
    $user = clientPortalUser($customer);

    $this->actingAs($user)->post(route('client.payments.store', $invoice));
    $payment = Payment::where('customer_id', $customer->id)->firstOrFail();

    $payload = json_encode([
        'event' => WebhookService::EVENT_PAYMENT_CONFIRMED,
        'external_id' => 'TX-BAD',
        'reference' => $payment->payment_reference,
        'transaction_id' => 'TX-BAD',
        'customer_number' => $customer->customer_number,
        'amount' => $payment->amount,
        'currency' => $payment->currency,
    ]);

    $result = app(WebhookService::class)->receive(
        $gateway,
        WebhookService::EVENT_PAYMENT_CONFIRMED,
        'TX-BAD',
        $payload,
        'sha256=signature-fausse',
    );

    expect($result['status'])->toBe('signature_failed');
    expect($payment->fresh()->status)->toBe(Payment::STATUS_PENDING);
    expect($invoice->fresh()->status)->toBe(Invoice::STATUS_ISSUED);
});
