<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payment_reference' => 'PAY-'.strtoupper(Str::random(12)),
            'customer_id' => Customer::factory(),
            'invoice_id' => null,
            'subscription_id' => null,
            'amount' => 10_000,
            'currency' => 'XOF',
            'method' => 'mobile_money',
            'provider' => 'orange_money',
            'status' => 'pending',
            'transaction_id' => Str::random(20),
            'provider_reference' => null,
            'paid_at' => null,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn () => [
            'status' => 'successful',
            'paid_at' => now(),
        ]);
    }
}
