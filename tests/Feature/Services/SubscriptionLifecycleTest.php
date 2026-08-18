<?php

use App\Models\OutboxEvent;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Services\BillingService;
use App\Services\SubscriptionLifecycleService;

function lifecycle(): SubscriptionLifecycleService
{
    return app(SubscriptionLifecycleService::class);
}

function billing(): BillingService
{
    return app(BillingService::class);
}

test('l\'activation d\'un abonnement le passe à actif et pose les dates', function () {
    $subscription = Subscription::factory()->create();

    $result = lifecycle()->activate($subscription);

    expect($result->status)->toBe('active');
    expect($result->starts_at)->not->toBeNull();
    expect($result->activated_at)->not->toBeNull();
    expect((int) $result->starts_at->diffInDays($result->expires_at))->toBe($result->offer->duration_days);
    expect($result->next_renewal_at->toDateString())->toBe($result->expires_at->toDateString());
});

test('l\'activation journalise un événement et publie l\'outbox', function () {
    $subscription = Subscription::factory()->create();
    lifecycle()->activate($subscription, source: 'admin', actorType: 'user', actorId: 1);

    expect(SubscriptionEvent::where('subscription_id', $subscription->id)->count())->toBe(1);
    $event = SubscriptionEvent::where('subscription_id', $subscription->id)->first();
    expect($event->event_type)->toBe('activated');
    expect($event->old_status)->toBe('pending');
    expect($event->new_status)->toBe('active');
    expect($event->source)->toBe('admin');
    expect($event->actor_type)->toBe('user');
    expect($event->actor_id)->toBe(1);

    expect(OutboxEvent::where('aggregate_uuid', $subscription->uuid)->where('event_type', 'SubscriptionActivated')->count())->toBe(1);
});

test('le cycle de vie complet pend activ grace suspend reactivate termine', function () {
    $subscription = Subscription::factory()->create();
    lifecycle()->activate($subscription);
    expect($subscription->fresh()->status)->toBe('active');

    lifecycle()->expire($subscription);
    expect($subscription->fresh()->status)->toBe('grace_period');

    lifecycle()->suspend($subscription, reason: 'unpaid');
    expect($subscription->fresh()->status)->toBe('suspended');
    expect($subscription->fresh()->suspension_reason)->toBe('unpaid');

    lifecycle()->reactivate($subscription);
    expect($subscription->fresh()->status)->toBe('active');
    expect($subscription->fresh()->suspended_at)->toBeNull();

    lifecycle()->renew($subscription);
    expect($subscription->fresh()->status)->toBe('active');
    expect($subscription->fresh()->renewals()->count())->toBe(1);

    lifecycle()->terminate($subscription, reason: 'customer_request');
    expect($subscription->fresh()->status)->toBe('terminated');
    expect($subscription->fresh()->termination_reason)->toBe('customer_request');

    expect(SubscriptionEvent::where('subscription_id', $subscription->id)->orderBy('id')->pluck('event_type')->all())
        ->toBe(['activated', 'expired', 'suspended', 'reactivated', 'renewed', 'terminated']);

    $outboxTypes = OutboxEvent::where('aggregate_uuid', $subscription->uuid)->orderBy('id')->pluck('event_type')->all();
    expect($outboxTypes)->toContain('SubscriptionActivated');
    expect($outboxTypes)->toContain('SubscriptionSuspended');
    expect($outboxTypes)->toContain('SubscriptionReactivated');
    expect($outboxTypes)->toContain('SubscriptionTerminated');
});

test('le renouvellement prolonge la date d\'expiration du montant de la période', function () {
    $subscription = Subscription::factory()->active()->create();
    $oldExpiry = $subscription->expires_at;

    lifecycle()->renew($subscription);

    expect($subscription->fresh()->expires_at->toDateString())
        ->toBe($oldExpiry->copy()->addDays($subscription->offer->duration_days)->toDateString());
    expect($subscription->fresh()->renewals()->first()->old_expires_at->toDateString())
        ->toBe($oldExpiry->toDateString());
});

test('la résiliation est possible depuis tout état non terminal', function () {
    $subscription = Subscription::factory()->create();
    lifecycle()->activate($subscription);
    lifecycle()->suspend($subscription);
    lifecycle()->terminate($subscription);

    expect($subscription->fresh()->status)->toBe('terminated');
});

test('l\'émission de facture crée une facture émise avec item et montants', function () {
    $subscription = Subscription::factory()->active()->create();

    $invoice = billing()->issueInvoiceForSubscription($subscription);

    expect($invoice->status)->toBe('issued');
    expect($invoice->total)->toBe($subscription->price);
    expect($invoice->amount_due)->toBe($subscription->price);
    expect($invoice->invoice_number)->toStartWith('INV-'.date('Y').'-');
    expect($invoice->items()->count())->toBe(1);
    expect($invoice->subscription_id)->toBe($subscription->id);
});

test('l\'application d\'un paiement solde la facture et réactive l\'abonnement', function () {
    $subscription = Subscription::factory()->create();
    lifecycle()->activate($subscription);
    lifecycle()->suspend($subscription, reason: 'unpaid');
    $invoice = billing()->issueInvoiceForSubscription($subscription);

    $payment = Payment::create([
        'payment_reference' => 'PAY-TEST-1',
        'customer_id' => $subscription->customer_id,
        'invoice_id' => $invoice->id,
        'subscription_id' => $subscription->id,
        'amount' => $invoice->total,
        'currency' => $invoice->currency,
        'method' => 'mobile_money',
        'provider' => 'orange_money',
        'status' => 'pending',
        'transaction_id' => 'TX-1',
    ]);

    billing()->applyPayment($payment);

    expect($payment->fresh()->status)->toBe('successful');
    expect($payment->fresh()->paid_at)->not->toBeNull();
    expect($invoice->fresh()->status)->toBe('paid');
    expect($invoice->fresh()->amount_due)->toBe(0);
    expect($invoice->fresh()->amount_paid)->toBe($invoice->total);
    expect($subscription->fresh()->status)->toBe('active');
});

test('un paiement partiel laisse la facture partiellement payée', function () {
    $subscription = Subscription::factory()->active()->create();
    $invoice = billing()->issueInvoiceForSubscription($subscription);

    $payment = Payment::create([
        'payment_reference' => 'PAY-TEST-2',
        'customer_id' => $subscription->customer_id,
        'invoice_id' => $invoice->id,
        'subscription_id' => $subscription->id,
        'amount' => $invoice->total - 1000,
        'currency' => $invoice->currency,
        'method' => 'mobile_money',
        'status' => 'pending',
        'transaction_id' => 'TX-2',
    ]);

    billing()->applyPayment($payment);

    expect($invoice->fresh()->status)->toBe('partially_paid');
    expect($invoice->fresh()->amount_due)->toBe(1000);
});
