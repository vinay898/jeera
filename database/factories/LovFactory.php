<?php

namespace Database\Factories;

use App\Enums\LovType;
use App\Models\Lov;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lov>
 */
class LovFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => null,
            'type' => $this->faker->randomElement(LovType::cases()),
            'name' => $this->faker->words(2, true),
            'value' => $this->faker->slug(2),
            'icon' => 'heroicon-o-tag',
            'color' => $this->faker->randomElement(['gray', 'danger', 'warning', 'success', 'info', 'purple']),
            'sort_order' => $this->faker->numberBetween(0, 10),
            'is_default' => false,
            'is_active' => true,
            'metadata' => null,
        ];
    }

    /**
     * Make the LOV global (no team).
     */
    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'team_id' => null,
        ]);
    }

    /**
     * Make the LOV team-specific.
     */
    public function forTeam(Team $team): static
    {
        return $this->state(fn (array $attributes) => [
            'team_id' => $team->id,
        ]);
    }

    /**
     * Set the LOV type.
     */
    public function ofType(LovType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Make this the default LOV.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Make this LOV inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a ticket type LOV.
     */
    public function ticketType(): static
    {
        return $this->ofType(LovType::TicketType);
    }

    /**
     * Create a ticket status LOV.
     */
    public function ticketStatus(): static
    {
        return $this->ofType(LovType::TicketStatus);
    }

    /**
     * Create a ticket priority LOV.
     */
    public function ticketPriority(): static
    {
        return $this->ofType(LovType::TicketPriority);
    }

    /**
     * Create a category LOV.
     */
    public function category(): static
    {
        return $this->ofType(LovType::TicketCategory);
    }
}
