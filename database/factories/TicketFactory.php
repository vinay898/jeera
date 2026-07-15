<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Project;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    private static int $ticketCounter = 1;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(TicketType::cases());
        $status = fake()->randomElement(TicketStatus::cases());

        return [
            'team_id' => Team::factory(),
            'project_id' => Project::factory(),
            'epic_id' => null,
            'parent_id' => null,
            'sprint_id' => null,
            'key' => 'TKT-'.self::$ticketCounter++,
            'title' => fake()->sentence(rand(4, 10)),
            'description' => fake()->optional(0.8)->paragraphs(rand(1, 3), true),
            'type' => $type,
            'status' => $status,
            'priority' => fake()->randomElement(TicketPriority::cases()),
            'assignee_id' => null,
            'reporter_id' => User::factory(),
            'story_points' => fake()->optional(0.6)->randomElement([1, 2, 3, 5, 8, 13]),
            'original_estimate' => fake()->optional(0.4)->numberBetween(30, 480),
            'time_spent' => 0,
            'time_remaining' => null,
            'due_date' => fake()->optional(0.3)->dateTimeBetween('now', '+30 days'),
            'resolution' => $status->isResolved() ? 'Fixed' : null,
            'resolved_at' => $status->isResolved() ? fake()->dateTimeBetween('-30 days', 'now') : null,
            'labels' => fake()->optional(0.3)->randomElements(['frontend', 'backend', 'api', 'ui', 'bug', 'feature'], rand(1, 3)),
            'custom_fields' => [],
            'watchers' => [],
        ];
    }

    public function bug(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TicketType::Bug,
            'priority' => fake()->randomElement([TicketPriority::High, TicketPriority::Highest]),
        ]);
    }

    public function story(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TicketType::Story,
            'story_points' => fake()->randomElement([3, 5, 8, 13]),
        ]);
    }

    public function task(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TicketType::Task,
        ]);
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::Open,
            'resolution' => null,
            'resolved_at' => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::InProgress,
            'resolution' => null,
            'resolved_at' => null,
        ]);
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::Done,
            'resolution' => 'Fixed',
            'resolved_at' => now(),
        ]);
    }

    public function withAssignee(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'assignee_id' => $user?->id ?? User::factory(),
        ]);
    }
}
