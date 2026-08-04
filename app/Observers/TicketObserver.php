<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\TicketStatus;
use App\Models\SlaConfiguration;
use App\Models\SlaTracking;
use App\Models\Ticket;

class TicketObserver
{
    /**
     * Handle the Ticket "created" event.
     * Creates SLA tracking based on the ticket's priority and project.
     */
    public function created(Ticket $ticket): void
    {
        $this->createSlaTracking($ticket);
    }

    /**
     * Handle the Ticket "updated" event.
     * Updates SLA tracking when status changes to resolved/closed.
     */
    public function updated(Ticket $ticket): void
    {
        if ($ticket->wasChanged('status')) {
            $this->handleStatusChange($ticket);
        }
    }

    /**
     * Create SLA tracking for the ticket.
     */
    protected function createSlaTracking(Ticket $ticket): void
    {
        $slaConfig = SlaConfiguration::query()
            ->where('team_id', $ticket->team_id)
            ->where(function ($query) use ($ticket) {
                $query->where('project_id', $ticket->project_id)
                    ->orWhereNull('project_id');
            })
            ->where('priority', $ticket->priority)
            ->where('is_active', true)
            ->orderByDesc('project_id')
            ->first();

        if (! $slaConfig) {
            return;
        }

        $now = now();

        SlaTracking::create([
            'ticket_id' => $ticket->id,
            'sla_configuration_id' => $slaConfig->id,
            'response_deadline_at' => $slaConfig->response_time_minutes
                ? $now->copy()->addMinutes($slaConfig->response_time_minutes)
                : null,
            'resolution_deadline_at' => $slaConfig->resolution_time_minutes
                ? $now->copy()->addMinutes($slaConfig->resolution_time_minutes)
                : null,
            'response_breached' => false,
            'resolution_breached' => false,
            'response_warning_sent' => false,
            'resolution_warning_sent' => false,
            'paused_duration_minutes' => 0,
        ]);
    }

    /**
     * Handle status changes for SLA tracking.
     */
    protected function handleStatusChange(Ticket $ticket): void
    {
        $slaTracking = $ticket->slaTracking;

        if (! $slaTracking) {
            return;
        }

        $resolvedStatuses = [
            TicketStatus::Done,
            TicketStatus::Closed,
        ];

        if (in_array($ticket->status, $resolvedStatuses, true)) {
            $slaTracking->recordResolution();
        }
    }
}
