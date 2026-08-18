<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(5_000, 50_000);

        return [
            'invoice_number' => 'INV-'.date('Y').'-'.strtoupper(Str::random(8)),
            'customer_id' => Customer::factory(),
            'subscription_id' => null,
            'status' => 'draft',
            'issue_date' => now(),
            'due_date' => now()->addDays(7),
            'subtotal' => $subtotal,
            'discount' => 0,
            'tax' => 0,
            'total' => $subtotal,
            'amount_paid' => 0,
            'amount_due' => $subtotal,
            'currency' => 'XOF',
            'paid_at' => null,
        ];
    }

    public function issued(): static
    {
        return $this->state(fn () => [
            'status' => 'issued',
            'amount_due' => 10_000,
            'total' => 10_000,
            'subtotal' => 10_000,
        ]);
    }
}
