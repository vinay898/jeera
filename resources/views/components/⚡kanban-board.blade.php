<?php

use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\Ticket;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url]
    public ?int $projectId = null;

    public function mount(?int $projectId = null): void
    {
        $this->projectId = $projectId;
    }

    #[Computed]
    public function team()
    {
        return Filament::getTenant();
    }

    #[Computed]
    public function projects(): Collection
    {
        return Project::where('team_id', $this->team->id)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function statuses(): array
    {
        return TicketStatus::cases();
    }

    #[Computed]
    public function ticketsByStatus(): array
    {
        $query = Ticket::with(['assignee', 'project'])
            ->where('team_id', $this->team->id);

        if ($this->projectId) {
            $query->where('project_id', $this->projectId);
        }

        $tickets = $query->orderBy('priority')->get();

        $grouped = [];
        foreach (TicketStatus::cases() as $status) {
            $grouped[$status->value] = $tickets->where('status', $status)->values();
        }

        return $grouped;
    }

    public function handleSort(int $ticketId, int $position, string $statusValue): void
    {
        $ticket = Ticket::where('team_id', $this->team->id)->findOrFail($ticketId);
        $newStatus = TicketStatus::from($statusValue);

        $ticket->update([
            'status' => $newStatus,
        ]);

        unset($this->ticketsByStatus);
    }

    public function getStatusHeaderColor(TicketStatus $status): string
    {
        return match ($status) {
            TicketStatus::Open => '#e5e7eb',
            TicketStatus::InProgress => '#dbeafe',
            TicketStatus::InReview => '#fef3c7',
            TicketStatus::Testing => '#ede9fe',
            TicketStatus::Done => '#d1fae5',
            TicketStatus::Closed => '#f3f4f6',
        };
    }

    public function getTypeColor(string $color): string
    {
        return match ($color) {
            'danger' => '#dc2626',
            'success' => '#16a34a',
            'info' => '#2563eb',
            'warning' => '#d97706',
            default => '#6b7280',
        };
    }

    public function getPriorityColor(string $color): string
    {
        return match ($color) {
            'danger' => '#ef4444',
            'warning' => '#f59e0b',
            'info' => '#3b82f6',
            default => '#9ca3af',
        };
    }
};
?>

<div>
    {{-- Project Filter --}}
    <div style="margin-bottom: 16px; display: flex; align-items: center; gap: 12px;">
        <label for="project-filter" style="font-size: 14px; font-weight: 500; color: #374151;">
            Filter by Project:
        </label>
        <select
            id="project-filter"
            wire:model.live="projectId"
            style="padding: 8px 12px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; background: white;"
        >
            <option value="">All Projects</option>
            @foreach ($this->projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Kanban Board --}}
    <div style="display: flex; gap: 16px; overflow-x: auto; padding-bottom: 16px; min-height: 70vh;">
        @foreach ($this->statuses as $status)
            <div style="flex-shrink: 0; width: 280px;">
                {{-- Column Header --}}
                <div style="background: {{ $this->getStatusHeaderColor($status) }}; border-radius: 8px 8px 0 0; padding: 12px; border: 1px solid #e5e7eb; border-bottom: none;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <h3 style="font-weight: 600; font-size: 14px; color: #111827; margin: 0;">
                            {{ $status->label() }}
                        </h3>
                        <span style="background: white; padding: 2px 8px; border-radius: 9999px; font-size: 12px; font-weight: 500; color: #6b7280;">
                            {{ count($this->ticketsByStatus[$status->value]) }}
                        </span>
                    </div>
                </div>

                {{-- Column Body --}}
                <div
                    wire:sort="handleSort"
                    wire:sort:group="tickets"
                    wire:sort:group-id="{{ $status->value }}"
                    style="background: #f9fafb; border-radius: 0 0 8px 8px; border: 1px solid #e5e7eb; border-top: none; padding: 8px; min-height: 400px;"
                >
                    @forelse ($this->ticketsByStatus[$status->value] as $ticket)
                        <div
                            wire:key="ticket-{{ $ticket->id }}"
                            wire:sort:item="{{ $ticket->id }}"
                            style="background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; padding: 12px; margin-bottom: 8px; cursor: grab;"
                        >
                            {{-- Ticket Header --}}
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                                <div style="display: flex; align-items: center; gap: 6px; min-width: 0;">
                                    <x-filament::icon
                                        :icon="$ticket->type->icon()"
                                        style="width: 16px; height: 16px; color: {{ $this->getTypeColor($ticket->type->color()) }};"
                                    />
                                    <span style="font-size: 12px; font-weight: 500; color: #6b7280;">
                                        {{ $ticket->key }}
                                    </span>
                                </div>
                                <x-filament::icon
                                    :icon="$ticket->priority->icon()"
                                    style="width: 16px; height: 16px; flex-shrink: 0; color: {{ $this->getPriorityColor($ticket->priority->color()) }};"
                                />
                            </div>

                            {{-- Ticket Title --}}
                            <p style="font-size: 14px; font-weight: 500; color: #111827; margin: 0 0 8px 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                {{ $ticket->title }}
                            </p>

                            {{-- Ticket Footer --}}
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span style="font-size: 11px; color: #6b7280; font-weight: 500;">
                                    {{ $ticket->project?->key ?? 'No Project' }}
                                </span>
                                @if ($ticket->assignee)
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: #dbeafe; display: flex; align-items: center; justify-content: center;">
                                        <span style="font-size: 10px; font-weight: 600; color: #1d4ed8;">
                                            {{ $ticket->assignee->initials() }}
                                        </span>
                                    </div>
                                @else
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                                        <x-filament::icon icon="heroicon-o-user" style="width: 12px; height: 12px; color: #9ca3af;" />
                                    </div>
                                @endif
                            </div>

                            @if ($ticket->story_points)
                                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #f3f4f6;">
                                    <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; background: #dbeafe; color: #1e40af;">
                                        {{ $ticket->story_points }} pts
                                    </span>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div style="display: flex; align-items: center; justify-content: center; height: 96px; color: #9ca3af; font-size: 14px;">
                            No tickets
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
