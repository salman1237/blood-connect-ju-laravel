<?php

namespace Database\Factories;

use App\Models\DonationHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonationHistory>
 */
class DonationHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'donor_id' => User::factory(),
            'request_id' => null,
            'confirmed_at' => now(),
        ];
    }
}
