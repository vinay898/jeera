<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public TeamInvitation $invitation,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've been invited to join {$this->invitation->team->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.team-invitation',
            with: [
                'invitation' => $this->invitation,
                'acceptUrl' => $this->getAcceptUrl(),
            ],
        );
    }

    /**
     * Get the invitation acceptance URL.
     */
    protected function getAcceptUrl(): string
    {
        return url("/invitations/{$this->invitation->token}/accept");
    }
}
