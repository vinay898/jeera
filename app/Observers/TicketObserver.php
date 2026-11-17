<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\TicketStatus;
use App\Models\SlaConfiguration;
use App\Models\SlaTracking;
use App\Models\Ticket;
use Illuminate\Support\Facades\Storage;

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
     * Handle the Ticket "saved" event.
     * Records files uploaded via the description editor as ticket attachments.
     */
    public function saved(Ticket $ticket): void
    {
        if ($ticket->wasRecentlyCreated || $ticket->wasChanged('description')) {
            $this->syncDescriptionAttachments($ticket);
        }
    }

    /**
     * Create Attachment rows for description-embedded uploads not yet recorded.
     */
    protected function syncDescriptionAttachments(Ticket $ticket): void
    {
        preg_match_all('/data-id="(ticket-descriptions\/[^"]+)"/', (string) $ticket->description, $matches);

        $paths = array_unique($matches[1]);

        if ($paths === []) {
            return;
        }

        $userId = auth()->id() ?? $ticket->reporter_id;

        if (! $userId) {
            return;
        }

        $disk = Storage::disk('public');
        $existingPaths = $ticket->attachments()->whereIn('path', $paths)->pluck('path')->all();

        foreach (array_diff($paths, $existingPaths) as $path) {
            if (! $disk->exists($path)) {
                continue;
            }

            $ticket->attachments()->create([
                'user_id' => $userId,
                'filename' => basename($path),
                'path' => $path,
                'mime_type' => $disk->mimeType($path) ?: 'application/octet-stream',
                'size' => $disk->size($path),
            ]);
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
