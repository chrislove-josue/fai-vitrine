<?php

use App\Models\Customer;
use App\Models\Invoice;

test('un client voit uniquement ses propres factures', function () {
    $mine = Customer::factory()->create();
    $other = Customer::factory()->create();
    $invoice = Invoice::factory()->issued()->create(['customer_id' => $mine->id]);
    Invoice::factory()->issued()->create(['customer_id' => $other->id]);
    $user = clientPortalUser($mine);

    $this->actingAs($user)->get(route('client.invoices.index'))
        ->assertOk()
        ->assertSee($invoice->invoice_number);

    $this->actingAs($user)->get(route('client.invoices.index'))
        ->assertDontSee($other->invoices()->first()->invoice_number);
});

test('le détail d\'une facture du client est accessible', function () {
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->issued()->create(['customer_id' => $customer->id]);
    $user = clientPortalUser($customer);

    $this->actingAs($user)->get(route('client.invoices.show', $invoice))
        ->assertOk()
        ->assertSee($invoice->invoice_number)
        ->assertSee('Détail');
});

test('le détail d\'une facture d\'un autre client est introuvable', function () {
    $customer = Customer::factory()->create();
    $other = Customer::factory()->create();
    $invoice = Invoice::factory()->issued()->create(['customer_id' => $other->id]);
    $user = clientPortalUser($customer);

    $this->actingAs($user)->get(route('client.invoices.show', $invoice))->assertNotFound();
});

test('le PDF d\'une facture est téléchargeable', function () {
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->issued()->create(['customer_id' => $customer->id]);
    $user = clientPortalUser($customer);

    $response = $this->actingAs($user)->get(route('client.invoices.pdf', $invoice));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

test('le PDF d\'une facture d\'un autre client est refusé', function () {
    $customer = Customer::factory()->create();
    $other = Customer::factory()->create();
    $invoice = Invoice::factory()->issued()->create(['customer_id' => $other->id]);
    $user = clientPortalUser($customer);

    $this->actingAs($user)->get(route('client.invoices.pdf', $invoice))->assertNotFound();
});

test('un client non rattaché à un compte ne voit aucune facture', function () {
    $user = clientPortalUser(Customer::factory()->create());
    $user->update(['customer_uuid' => null]);

    $this->actingAs($user)->get(route('client.invoices.index'))
        ->assertOk()
        ->assertSee('Aucun compte client');
});
