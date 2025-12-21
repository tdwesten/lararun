<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'strava_id' => $this->faker->unique()->randomNumber(8),
            'name' => $this->faker->sentence(3),
            'type' => 'Run',
            'distance' => $this->faker->randomFloat(2, 1000, 10000),
            'moving_time' => $this->faker->numberBetween(300, 3600),
            'elapsed_time' => $this->faker->numberBetween(300, 3600),
            'start_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'zone_data_available' => true,
            'intensity_score' => $this->faker->randomFloat(2, 20, 150),
            'short_evaluation' => $this->faker->sentence(),
            'extended_evaluation' => $this->faker->paragraph(),
        ];
    }
}
