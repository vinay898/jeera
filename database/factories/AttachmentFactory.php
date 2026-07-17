<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->word().'.'.fake()->randomElement(['pdf', 'doc', 'txt', 'png', 'jpg']);
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'txt' => 'text/plain',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
        ];
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'filename' => $filename,
            'path' => 'attachments/tickets/'.fake()->uuid().'.'.$extension,
            'mime_type' => $mimeTypes[$extension] ?? 'application/octet-stream',
            'size' => fake()->numberBetween(1024, 10485760),
        ];
    }

    public function image(): static
    {
        return $this->state(function (array $attributes) {
            $extension = fake()->randomElement(['png', 'jpg', 'gif', 'webp']);
            $mimeTypes = [
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
            ];

            return [
                'filename' => fake()->word().'.'.$extension,
                'path' => 'attachments/tickets/'.fake()->uuid().'.'.$extension,
                'mime_type' => $mimeTypes[$extension],
            ];
        });
    }

    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'filename' => fake()->word().'.pdf',
            'path' => 'attachments/tickets/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }

    public function forTicket(Ticket $ticket): static
    {
        return $this->state(fn (array $attributes) => [
            'ticket_id' => $ticket->id,
        ]);
    }

    public function uploadedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
