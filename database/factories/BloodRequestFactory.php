<?php

namespace Database\Factories;

use App\Models\BloodRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BloodRequest>
 */
class BloodRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
            'blood_group' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'units_needed' => $this->faker->numberBetween(1, 4),
            'hospital_name' => $this->faker->company().' Hospital',
            'location' => $this->faker->city(),
            'urgency' => $this->faker->randomElement(['critical', 'within_24h', 'planned']),
            'patient_context' => $this->faker->optional()->sentence(),
            'contact_method' => '01'.$this->faker->numerify('#########'),
            'status' => 'open',
            'is_verified' => false,
            'expires_at' => now()->addHours(BloodRequest::EXPIRES_AFTER_HOURS),
        ];
    }

    public function critical(): static
    {
        return $this->state(['urgency' => 'critical']);
    }

    public function verified(): static
    {
        return $this->state(['is_verified' => true]);
    }

    public function fulfilled(): static
    {
        return $this->state(['status' => 'fulfilled']);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => 'expired',
            'expires_at' => now()->subHour(),
        ]);
    }
}
