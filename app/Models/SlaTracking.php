<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ticket_id',
    'sla_configuration_id',
    'response_deadline_at',
    'resolution_deadline_at',
    'first_response_at',
    'resolved_at',
    'response_breached',
    'resolution_breached',
    'response_warning_sent',
    'resolution_warning_sent',
    'paused_at',
    'paused_duration_minutes',
])]
class SlaTracking extends Model
{
    protected $table = 'sla_tracking';

    protected function casts(): array
    {
        return [
            'response_deadline_at' => 'datetime',
            'resolution_deadline_at' => 'datetime',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'response_breached' => 'boolean',
            'resolution_breached' => 'boolean',
            'response_warning_sent' => 'boolean',
            'resolution_warning_sent' => 'boolean',
            'paused_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<SlaConfiguration, $this>
     */
    public function slaConfiguration(): BelongsTo
    {
        return $this->belongsTo(SlaConfiguration::class);
    }

    /**
     * Check if the response SLA is breached.
     */
    public function isResponseBreached(): bool
    {
        if ($this->first_response_at || ! $this->response_deadline_at) {
            return false;
        }

        return now()->isAfter($this->response_deadline_at);
    }

    /**
     * Check if the resolution SLA is breached.
     */
    public function isResolutionBreached(): bool
    {
        if ($this->resolved_at || ! $this->resolution_deadline_at) {
            return false;
        }

        return now()->isAfter($this->resolution_deadline_at);
    }

    /**
     * Check if response is approaching deadline (within warning threshold).
     */
    public function isResponseWarningDue(int $warningMinutes = 30): bool
    {
        if ($this->first_response_at || $this->response_warning_sent || ! $this->response_deadline_at) {
            return false;
        }

        return now()->addMinutes($warningMinutes)->isAfter($this->response_deadline_at);
    }

    /**
     * Check if resolution is approaching deadline (within warning threshold).
     */
    public function isResolutionWarningDue(int $warningMinutes = 60): bool
    {
        if ($this->resolved_at || $this->resolution_warning_sent || ! $this->resolution_deadline_at) {
            return false;
        }

        return now()->addMinutes($warningMinutes)->isAfter($this->resolution_deadline_at);
    }

    /**
     * Record the first response.
     */
    public function recordFirstResponse(): void
    {
        if (! $this->first_response_at) {
            $this->update(['first_response_at' => now()]);
        }
    }

    /**
     * Record resolution.
     */
    public function recordResolution(): void
    {
        if (! $this->resolved_at) {
            $this->update(['resolved_at' => now()]);
        }
    }

    /**
     * Pause SLA tracking.
     */
    public function pause(): void
    {
        if (! $this->paused_at) {
            $this->update(['paused_at' => now()]);
        }
    }

    /**
     * Resume SLA tracking after a pause.
     */
    public function resume(): void
    {
        if ($this->paused_at) {
            $pausedMinutes = $this->paused_at->diffInMinutes(now());
            $this->update([
                'paused_at' => null,
                'paused_duration_minutes' => $this->paused_duration_minutes + $pausedMinutes,
            ]);
        }
    }
}
