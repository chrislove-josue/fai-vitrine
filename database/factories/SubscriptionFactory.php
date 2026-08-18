<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Offer;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $offer = Offer::factory()->create();

        return [
            'subscription_number' => 'SUB-'.strtoupper(Str::random(8)),
            'customer_id' => Customer::factory(),
            'offer_id' => $offer->id,
            'status' => 'pending',
            'starts_at' => null,
            'expires_at' => null,
            'activated_at' => null,
            'suspended_at' => null,
            'cancelled_at' => null,
            'terminated_at' => null,
            'auto_renew' => false,
            'price' => 10_000,
            'currency' => 'XOF',
            'next_renewal_at' => null,
        ];
    }

    public function active(): static
    {
        $startsAt = now()->subDays(10);

        return $this->state(fn () => [
            'status' => 'active',
            'starts_at' => $startsAt,
            'expires_at' => $startsAt->copy()->addDays(30),
            'activated_at' => $startsAt,
            'next_renewal_at' => $startsAt->copy()->addDays(30),
        ]);
    }
}
