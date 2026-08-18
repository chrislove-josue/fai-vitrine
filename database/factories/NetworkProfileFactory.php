<?php

namespace Database\Factories;

use App\Models\NetworkProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NetworkProfile>
 */
class NetworkProfileFactory extends Factory
{
    protected $model = NetworkProfile::class;

    public function definition(): array
    {
        return [
            'code' => 'NP-'.strtoupper(Str::random(8)),
            'name' => fake()->words(2, true),
            'download_speed' => 20_000_000,
            'upload_speed' => 20_000_000,
            'rate_limit' => null,
            'burst_limit' => null,
            'burst_threshold' => null,
            'burst_time' => null,
            'priority' => 0,
            'session_timeout' => null,
            'idle_timeout' => null,
            'data_limit' => null,
            'status' => 'active',
        ];
    }
}
