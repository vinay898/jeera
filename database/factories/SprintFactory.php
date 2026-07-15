<?php

namespace Database\Factories;

use App\Enums\SprintStatus;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sprint>
 */
class SprintFactory extends Factory
{
    private static int $sprintCounter = 1;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-30 days', '+7 days');
        $endDate = (clone $startDate)->modify('+14 days');

        return [
            'team_id' => Team::factory(),
            'project_id' => Project::factory(),
            'name' => 'Sprint '.self::$sprintCounter++,
            'goal' => fake()->optional(0.7)->sentence(rand(6, 12)),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => fake()->randomElement(SprintStatus::cases()),
        ];
    }

    public function planning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SprintStatus::Planning,
            'start_date' => now()->addDays(rand(1, 7)),
            'end_date' => now()->addDays(rand(14, 21)),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SprintStatus::Active,
            'start_date' => now()->subDays(rand(1, 7)),
            'end_date' => now()->addDays(rand(7, 14)),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SprintStatus::Completed,
            'start_date' => now()->subDays(rand(14, 28)),
            'end_date' => now()->subDays(rand(1, 7)),
        ]);
    }
}
