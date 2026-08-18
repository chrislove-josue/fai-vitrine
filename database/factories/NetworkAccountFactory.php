<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\NetworkAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NetworkAccount>
 */
class NetworkAccountFactory extends Factory
{
    protected $model = NetworkAccount::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'username' => 'acc-'.Str::lower(Str::random(10)),
            'authentication_type' => 'pap',
            'status' => 'pending',
            'mac_auth_enabled' => false,
        ];
    }
}
