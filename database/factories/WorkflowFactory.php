<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workflow>
 */
class WorkflowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->randomElement(['Default', 'Agile', 'Kanban', 'Simplified']).' Workflow',
            'is_default' => false,
            'statuses' => [
                ['name' => 'Open', 'category' => 'todo'],
                ['name' => 'In Progress', 'category' => 'in_progress'],
                ['name' => 'In Review', 'category' => 'in_progress'],
                ['name' => 'Done', 'category' => 'done'],
            ],
            'transitions' => [
                ['from' => 'Open', 'to' => 'In Progress'],
                ['from' => 'In Progress', 'to' => 'In Review'],
                ['from' => 'In Review', 'to' => 'Done'],
                ['from' => 'In Review', 'to' => 'In Progress'],
                ['from' => 'Done', 'to' => 'Open'],
            ],
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Default Workflow',
            'is_default' => true,
        ]);
    }

    public function simplified(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Simplified Workflow',
            'statuses' => [
                ['name' => 'To Do', 'category' => 'todo'],
                ['name' => 'Doing', 'category' => 'in_progress'],
                ['name' => 'Done', 'category' => 'done'],
            ],
            'transitions' => [
                ['from' => 'To Do', 'to' => 'Doing'],
                ['from' => 'Doing', 'to' => 'Done'],
                ['from' => 'Done', 'to' => 'To Do'],
            ],
        ]);
    }
}
