<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Objective>
 */
class ObjectiveFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'type' => $this->faker->randomElement(['5 km', '10 km', '21.1 km', '42.2 km', 'Speed']),
            'target_date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'status' => 'active',
            'description' => $this->faker->sentence(),
            'running_days' => ['Monday', 'Wednesday', 'Friday'],
        ];
    }
}
