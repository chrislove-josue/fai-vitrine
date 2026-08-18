<?php

namespace Database\Factories;

use App\Models\NetworkProfile;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    protected $model = Offer::class;

    public function definition(): array
    {
        return [
            'code' => 'OFF-'.strtoupper(Str::random(8)),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'status' => 'active',
            'duration_days' => 30,
            'network_profile_id' => NetworkProfile::factory(),
            'activation_fee' => 0,
            'currency' => 'XOF',
            'max_simultaneous_sessions' => 1,
            'data_limit' => null,
            'fair_use_limit' => null,
        ];
    }
}
