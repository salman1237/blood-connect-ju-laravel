<?php

namespace Database\Factories;

use App\Models\BloodRequest;
use App\Models\RequestResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestResponse>
 */
class RequestResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'request_id' => BloodRequest::factory(),
            'donor_id' => User::factory(),
            'status' => 'responded',
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => 'confirmed']);
    }
}
