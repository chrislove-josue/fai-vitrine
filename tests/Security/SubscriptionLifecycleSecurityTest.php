<?php

use App\Models\Payment;
use App\Models\Subscription;
use App\Services\BillingService;
use App\Services\SubscriptionLifecycleService;

test('une transition invalide est refusée sans modifier l\'état', function () {
    $subscription = Subscription::factory()->create();
    $lifecycle = app(SubscriptionLifecycleService::class);

    expect(fn () => $lifecycle->suspend($subscription))
        ->toThrow(InvalidArgumentException::class);

    expect($subscription->fresh()->status)->toBe('pending');
    expect($subscription->fresh()->suspended_at)->toBeNull();
});

test('un abonnement résilié ne peut pas être réactivé', function () {
    $subscription = Subscription::factory()->create();
    $lifecycle = app(SubscriptionLifecycleService::class);
    $lifecycle->activate($subscription);
    $lifecycle->terminate($subscription, reason: 'customer_request');

    expect(fn () => $lifecycle->reactivate($subscription))
        ->toThrow(InvalidArgumentException::class);
    expect($subscription->fresh()->status)->toBe('terminated');
});

test('un paiement déjà appliqué est refusé (idempotence)', function () {
    $subscription = Subscription::factory()->active()->create();
    $billing = app(BillingService::class);
    $invoice = $billing->issueInvoiceForSubscription($subscription);

    $payment = Payment::create([
        'payment_reference' => 'PAY-DUP-1',
        'customer_id' => $subscription->customer_id,
        'invoice_id' => $invoice->id,
        'subscription_id' => $subscription->id,
        'amount' => $invoice->total,
        'currency' => $invoice->currency,
        'method' => 'mobile_money',
        'status' => 'successful',
        'paid_at' => now(),
        'transaction_id' => 'TX-DUP',
    ]);

    expect(fn () => $billing->applyPayment($payment))
        ->toThrow(InvalidArgumentException::class);
    expect($invoice->fresh()->amount_paid)->toBe(0);
    expect($invoice->fresh()->status)->toBe('issued');
});

test('un paiement sur une facture annulée est refusé', function () {
    $subscription = Subscription::factory()->active()->create();
    $billing = app(BillingService::class);
    $invoice = $billing->issueInvoiceForSubscription($subscription);
    $invoice->update(['status' => 'cancelled']);

    $payment = Payment::create([
        'payment_reference' => 'PAY-CANCEL-1',
        'customer_id' => $subscription->customer_id,
        'invoice_id' => $invoice->id,
        'subscription_id' => $subscription->id,
        'amount' => $invoice->total,
        'currency' => $invoice->currency,
        'method' => 'mobile_money',
        'status' => 'pending',
        'transaction_id' => 'TX-CANCEL',
    ]);

    expect(fn () => $billing->applyPayment($payment))
        ->toThrow(InvalidArgumentException::class);
});

test('la facturation d\'un abonnement en attente est refusée', function () {
    $subscription = Subscription::factory()->create();
    $billing = app(BillingService::class);

    expect(fn () => $billing->issueInvoiceForSubscription($subscription))
        ->toThrow(InvalidArgumentException::class);
});

test('aucune transition de cycle de vie ne peut sortir d\'un état résilié ou annulé', function () {
    $lifecycle = app(SubscriptionLifecycleService::class);

    foreach (['terminated', 'cancelled'] as $terminal) {
        $subscription = Subscription::factory()->create(['status' => $terminal]);
        expect(fn () => $lifecycle->activate($subscription))->toThrow(InvalidArgumentException::class);
        expect(fn () => $lifecycle->expire($subscription))->toThrow(InvalidArgumentException::class);
        expect(fn () => $lifecycle->suspend($subscription))->toThrow(InvalidArgumentException::class);
        expect(fn () => $lifecycle->reactivate($subscription))->toThrow(InvalidArgumentException::class);
        expect(fn () => $lifecycle->renew($subscription))->toThrow(InvalidArgumentException::class);
    }
});
