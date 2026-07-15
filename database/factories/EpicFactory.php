<?php

namespace Database\Factories;

use App\Enums\EpicStatus;
use App\Models\Epic;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Epic>
 */
class EpicFactory extends Factory
{
    private static int $epicCounter = 1;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->optional(0.7)->dateTimeBetween('-30 days', '+30 days');

        return [
            'team_id' => Team::factory(),
            'project_id' => Project::factory(),
            'key' => 'EPIC-'.self::$epicCounter++,
            'title' => fake()->sentence(rand(3, 6)),
            'description' => fake()->optional(0.7)->paragraphs(rand(1, 2), true),
            'status' => fake()->randomElement(EpicStatus::cases()),
            'start_date' => $startDate,
            'end_date' => $startDate ? fake()->dateTimeBetween($startDate, '+90 days') : null,
            'color' => fake()->optional(0.5)->hexColor(),
        ];
    }

    public function todo(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EpicStatus::Todo,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EpicStatus::InProgress,
        ]);
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EpicStatus::Done,
        ]);
    }
}
