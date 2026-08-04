<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id',
    'project_id',
    'inbound_email',
    'inbound_secret',
    'from_name',
    'from_address',
    'signature',
    'auto_create_tickets',
    'default_ticket_type',
    'default_priority',
    'is_active',
    'imap_host',
    'imap_port',
    'imap_encryption',
    'imap_username',
    'imap_password',
    'imap_mailbox',
    'poll_interval_seconds',
    'last_polled_at',
    'imap_enabled',
])]
class EmailConfiguration extends Model
{
    protected function casts(): array
    {
        return [
            'auto_create_tickets' => 'boolean',
            'default_ticket_type' => TicketType::class,
            'default_priority' => TicketPriority::class,
            'is_active' => 'boolean',
            'imap_enabled' => 'boolean',
            'last_polled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Generate a new inbound secret for webhook validation.
     */
    public function regenerateSecret(): void
    {
        $this->inbound_secret = bin2hex(random_bytes(32));
        $this->save();
    }

    /**
     * Check if this configuration is due for polling.
     */
    public function isDueForPolling(): bool
    {
        if (! $this->imap_enabled || ! $this->is_active) {
            return false;
        }

        if (! $this->last_polled_at) {
            return true;
        }

        return $this->last_polled_at->addSeconds($this->poll_interval_seconds)->isPast();
    }

    /**
     * Check if IMAP is properly configured.
     */
    public function hasImapConfiguration(): bool
    {
        return $this->imap_host
            && $this->imap_username
            && $this->imap_password;
    }

    /**
     * Get the IMAP connection string.
     */
    public function getImapConnectionString(): string
    {
        $encryption = match ($this->imap_encryption) {
            'ssl' => '/ssl',
            'tls' => '/tls',
            default => '',
        };

        return sprintf(
            '{%s:%d/imap%s}%s',
            $this->imap_host,
            $this->imap_port,
            $encryption,
            $this->imap_mailbox
        );
    }
}
