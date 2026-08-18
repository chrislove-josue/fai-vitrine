<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'customer_number' => 'CUS-'.strtoupper(Str::random(8)),
            'type' => 'individual',
            'status' => 'active',
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company_name' => null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'birth_date' => fake()->date('Y-m-d', '-18 years'),
        ];
    }

    public function company(): static
    {
        return $this->state(fn () => [
            'type' => 'company',
            'first_name' => null,
            'last_name' => null,
            'company_name' => fake()->company(),
        ]);
    }

    public function prospect(): static
    {
        return $this->state(fn () => ['status' => 'prospect']);
    }

    public function individual(): static
    {
        return $this->state(fn () => [
            'type' => 'individual',
            'company_name' => null,
        ]);
    }
}
