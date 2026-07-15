<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(rand(2, 4), true);

        return [
            'team_id' => Team::factory(),
            'name' => ucwords($name),
            'key' => strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 4)),
            'description' => fake()->optional(0.7)->paragraph(),
            'lead_user_id' => null,
            'default_assignee_id' => null,
            'workflow_id' => null,
            'is_archived' => fake()->boolean(10),
        ];
    }

    public function withLead(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'lead_user_id' => $user?->id ?? User::factory(),
        ]);
    }

    public function withWorkflow(?Workflow $workflow = null): static
    {
        return $this->state(fn (array $attributes) => [
            'workflow_id' => $workflow?->id ?? Workflow::factory(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_archived' => true,
        ]);
    }
}
