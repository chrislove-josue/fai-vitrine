<?php

use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('les clients ne sont jamais supprimés physiquement (suppression logique)', function () {
    $customer = Customer::factory()->create();
    $count = Customer::count();

    $customer->delete();

    expect(Customer::count())->toBe($count - 1);
    expect(Customer::withTrashed()->count())->toBe($count);
    expect(DB::connection('isp_core')->table('customers')->count())->toBe($count);
});

test('les données clients sont uniquement sur isp_core et jamais sur isp_application', function () {
    $customer = Customer::factory()->create(['email' => 'confidential@example.com']);

    expect(Schema::connection('isp_application')->hasTable('customers'))->toBeFalse();
    expect(DB::connection('isp_application')->table('users')->where('email', 'confidential@example.com')->count())->toBe(0);
});

test('les abonnements supprimés conservent leur historique', function () {
    $subscription = Subscription::factory()->active()->create();
    $subscription->events()->create([
        'event_type' => 'activated',
        'old_status' => 'pending',
        'new_status' => 'active',
    ]);

    $subscription->delete();

    expect(Subscription::withTrashed()->find($subscription->id)->events()->count())->toBe(1);
});
