<?php

use App\Services\OutboxService;
use App\Services\ReferenceGenerator;
use App\Services\SubscriptionLifecycleService;

test('le générateur de référence produit des préfixes attendus', function () {
    expect(ReferenceGenerator::customerNumber())->toStartWith('CUS-');
    expect(ReferenceGenerator::subscriptionNumber())->toStartWith('SUB-');
    expect(ReferenceGenerator::invoiceNumber())->toStartWith('INV-'.date('Y').'-');
    expect(ReferenceGenerator::paymentReference())->toStartWith('PAY-'.date('Ymd').'-');
    expect(ReferenceGenerator::creditNoteNumber())->toStartWith('CN-'.date('Y').'-');
});

test('les références générées sont uniques', function () {
    $values = [];

    for ($i = 0; $i < 100; $i++) {
        $values[] = ReferenceGenerator::invoiceNumber();
    }

    expect(count(array_unique($values)))->toBe(100);
});

test('la machine à états accepte les transitions valides du cycle de vie', function () {
    $service = new SubscriptionLifecycleService(new OutboxService);

    expect($service->canTransition('pending', 'active'))->toBeTrue();
    expect($service->canTransition('active', 'grace_period'))->toBeTrue();
    expect($service->canTransition('active', 'suspended'))->toBeTrue();
    expect($service->canTransition('grace_period', 'suspended'))->toBeTrue();
    expect($service->canTransition('suspended', 'active'))->toBeTrue();
    expect($service->canTransition('active', 'terminated'))->toBeTrue();
    expect($service->canTransition('grace_period', 'active'))->toBeTrue();
});

test('la machine à états refuse les transitions invalides', function () {
    $service = new SubscriptionLifecycleService(new OutboxService);

    expect($service->canTransition('terminated', 'active'))->toBeFalse();
    expect($service->canTransition('pending', 'suspended'))->toBeFalse();
    expect($service->canTransition('pending', 'grace_period'))->toBeFalse();
    expect($service->canTransition('expired', 'suspended'))->toBeFalse();
});

test('le cycle de vie complet respecte l\'ordre du cahier des charges', function () {
    $flow = ['pending', 'active', 'grace_period', 'suspended', 'active', 'terminated'];
    $service = new SubscriptionLifecycleService(new OutboxService);

    foreach (array_slice($flow, 0, -1) as $i => $from) {
        expect($service->canTransition($from, $flow[$i + 1]))
            ->toBeTrue("Transition {$from} → {$flow[$i + 1]} refusée");
    }
});
