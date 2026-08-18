<?php

use App\Models\Address;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\CustomerDocument;

test('le profil affiche l\'identité, les contacts, adresses et documents du client', function () {
    $customer = Customer::factory()->create(['first_name' => 'Alice', 'last_name' => 'Dupont']);
    CustomerContact::create([
        'customer_id' => $customer->id,
        'type' => 'email',
        'value' => 'alice.contact@example.com',
        'is_primary' => true,
    ]);
    Address::create([
        'customer_id' => $customer->id,
        'type' => 'billing',
        'address_line_1' => '12 Avenue des Palmiers',
        'city' => 'Abidjan',
        'country' => 'CI',
    ]);
    CustomerDocument::create([
        'customer_id' => $customer->id,
        'type' => 'identity_card',
        'file_name' => 'cnib-alice.pdf',
        'file_path' => '/documents/cnib-alice.pdf',
        'status' => 'verified',
    ]);
    $user = clientPortalUser($customer);

    $this->actingAs($user)->get(route('client.profile.show'))
        ->assertOk()
        ->assertSee('Alice Dupont')
        ->assertSee('CUS-')
        ->assertSee('alice.contact@example.com')
        ->assertSee('12 Avenue des Palmiers')
        ->assertSee('cnib-alice.pdf');
});

test('un client sans compte rattaché n\'a pas de page profil', function () {
    $user = clientPortalUser(Customer::factory()->create());
    $user->update(['customer_uuid' => null]);

    $this->actingAs($user)->get(route('client.profile.show'))->assertNotFound();
});
