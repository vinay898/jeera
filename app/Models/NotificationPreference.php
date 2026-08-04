<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'team_id',
    'email_ticket_assigned',
    'email_ticket_commented',
    'email_ticket_mentioned',
    'email_ticket_status_changed',
    'email_ticket_closed',
    'email_sla_warning',
    'email_sla_breach',
    'email_daily_digest',
    'digest_time',
    'quiet_hours_start',
    'quiet_hours_end',
])]
class NotificationPreference extends Model
{
    protected function casts(): array
    {
        return [
            'email_ticket_assigned' => 'boolean',
            'email_ticket_commented' => 'boolean',
            'email_ticket_mentioned' => 'boolean',
            'email_ticket_status_changed' => 'boolean',
            'email_ticket_closed' => 'boolean',
            'email_sla_warning' => 'boolean',
            'email_sla_breach' => 'boolean',
            'email_daily_digest' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Check if notifications are allowed based on quiet hours.
     */
    public function isWithinQuietHours(): bool
    {
        if (! $this->quiet_hours_start || ! $this->quiet_hours_end) {
            return false;
        }

        $now = now()->format('H:i:s');

        if ($this->quiet_hours_start <= $this->quiet_hours_end) {
            return $now >= $this->quiet_hours_start && $now <= $this->quiet_hours_end;
        }

        // Quiet hours span midnight
        return $now >= $this->quiet_hours_start || $now <= $this->quiet_hours_end;
    }

    /**
     * Check if a specific notification type is enabled.
     */
    public function isEnabled(string $notificationType): bool
    {
        $field = "email_{$notificationType}";

        return $this->$field ?? true;
    }
}
