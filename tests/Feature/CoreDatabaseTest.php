<?php

use App\Models\Customer;
use Illuminate\Support\Facades\Schema;

test('toutes les tables isp_core sont créées', function () {
    $tables = [
        'customers', 'customer_contacts', 'addresses', 'customer_documents',
        'network_profiles', 'offers', 'offer_prices',
        'subscriptions', 'subscription_events', 'subscription_renewals', 'subscription_network_accounts',
        'network_accounts', 'customer_devices', 'network_sessions',
        'invoices', 'invoice_items', 'payments', 'payment_attempts', 'refunds', 'credit_notes',
        'notification_templates', 'notification_rules', 'notifications',
    ];

    foreach ($tables as $table) {
        expect(Schema::connection('isp_core')->hasTable($table))
            ->toBeTrue("Table {$table} absente sur isp_core");
    }
});

test('les tables core possèdent un uuid unique et des index sur les colonnes clés', function () {
    $schema = Schema::connection('isp_core');

    foreach (['customers', 'offers', 'subscriptions', 'invoices', 'payments', 'network_accounts'] as $table) {
        expect($schema->getColumnListing($table))->toContain('uuid', 'id');
    }
});

test('la table customers respecte les colonnes du cahier des charges', function () {
    $columns = Schema::connection('isp_core')->getColumnListing('customers');

    expect($columns)->toContain(
        'customer_number', 'type', 'status', 'first_name', 'last_name',
        'company_name', 'email', 'phone', 'birth_date', 'deleted_at',
    );
});

test('les tables core sont isolées sur la connexion isp_core', function () {
    expect(Schema::connection('isp_core')->hasTable('users'))->toBeFalse();
    expect(Schema::connection('isp_core')->hasTable('roles'))->toBeFalse();
    expect(Schema::connection('isp_application')->hasTable('customers'))->toBeFalse();
});

test('un client est enregistré sur isp_core avec un uuid', function () {
    $customer = Customer::factory()->create();

    expect($customer->getConnectionName())->toBe('isp_core');
    expect($customer->uuid)->toBeUuid();
    expect($customer->customer_number)->toStartWith('CUS-');
});
