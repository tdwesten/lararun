<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DailyRecommendation>
 */
class DailyRecommendationFactory extends Factory
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
            'objective_id' => \App\Models\Objective::factory(),
            'date' => now()->toDateString(),
            'type' => $this->faker->randomElement(['Easy Run', 'Intervals', 'Long Run', 'Rest']),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'reasoning' => $this->faker->paragraph(),
        ];
    }
}
