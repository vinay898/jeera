<?php

use App\Enums\LovType;
use App\Models\Epic;
use App\Models\Lov;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    #[Url]
    public ?int $projectId = null;

    #[Url]
    public ?int $epicId = null;

    #[Url]
    public ?int $assigneeId = null;

    public ?int $editingTicketId = null;

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
    public function projects(): \Illuminate\Support\Collection
    {
        return Project::where('team_id', $this->team->id)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function epics(): \Illuminate\Support\Collection
    {
        $query = Epic::where('team_id', $this->team->id);

        if ($this->projectId) {
            $query->where('project_id', $this->projectId);
        }

        return $query->orderBy('title')->get();
    }

    #[Computed]
    public function teamUsers(): \Illuminate\Support\Collection
    {
        return $this->team->users()->orderBy('name')->get();
    }

    #[Computed]
    public function statuses(): Collection
    {
        return Lov::getForTeam(LovType::TicketStatus, $this->team->id);
    }

    #[Computed]
    public function types(): Collection
    {
        return Lov::getForTeam(LovType::TicketType, $this->team->id);
    }

    #[Computed]
    public function priorities(): Collection
    {
        return Lov::getForTeam(LovType::TicketPriority, $this->team->id);
    }

    #[Computed]
    public function activeFilterCount(): int
    {
        $count = 0;
        if ($this->projectId) {
            $count++;
        }
        if ($this->epicId) {
            $count++;
        }
        if ($this->assigneeId) {
            $count++;
        }

        return $count;
    }

    #[Computed]
    public function ticketsByStatus(): array
    {
        $query = Ticket::with(['assignee', 'project', 'epic'])
            ->where('team_id', $this->team->id);

        if ($this->projectId) {
            $query->where('project_id', $this->projectId);
        }

        if ($this->epicId) {
            $query->where('epic_id', $this->epicId);
        }

        if ($this->assigneeId) {
            $query->where('assignee_id', $this->assigneeId);
        }

        $tickets = $query->orderBy('priority')->get();

        $grouped = [];
        foreach ($this->statuses as $status) {
            $grouped[$status->value] = $tickets->where('status', $status->value)->values();
        }

        return $grouped;
    }

    public function updatedProjectId(): void
    {
        // Reset epic filter when project changes
        $this->epicId = null;
        unset($this->epics);
        unset($this->ticketsByStatus);
    }

    public function updatedEpicId(): void
    {
        unset($this->ticketsByStatus);
    }

    public function updatedAssigneeId(): void
    {
        unset($this->ticketsByStatus);
    }

    public function clearFilters(): void
    {
        $this->projectId = null;
        $this->epicId = null;
        $this->assigneeId = null;
        unset($this->epics);
        unset($this->ticketsByStatus);
    }

    public function handleSort(int $ticketId, int $position, string $statusValue): void
    {
        $ticket = Ticket::where('team_id', $this->team->id)->findOrFail($ticketId);

        $ticket->update([
            'status' => $statusValue,
        ]);

        unset($this->ticketsByStatus);
    }

    public function openEditModal(int $ticketId): void
    {
        $this->editingTicketId = $ticketId;
        $this->mountAction('editTicket');
    }

    public function createTicketAction(): Action
    {
        $teamId = $this->team->id;

        return Action::make('createTicket')
            ->label('Ticket')
            ->icon(Heroicon::OutlinedTicket)
            ->modalHeading('Create Ticket')
            ->modalWidth('4xl')
            ->schema([
                Section::make('Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Select::make('project_id')
                            ->label('Project')
                            ->options(fn () => Project::where('team_id', $teamId)->pluck('name', 'id'))
                            ->required()
                            ->default($this->projectId)
                            ->searchable(),
                        Select::make('type')
                            ->options(fn () => Lov::getOptions(LovType::TicketType, $teamId))
                            ->default(fn () => Lov::getDefault(LovType::TicketType, $teamId)?->value ?? 'task')
                            ->required(),
                        Select::make('status')
                            ->options(fn () => Lov::getOptions(LovType::TicketStatus, $teamId))
                            ->default(fn () => Lov::getDefault(LovType::TicketStatus, $teamId)?->value ?? 'open')
                            ->required(),
                        Select::make('priority')
                            ->options(fn () => Lov::getOptions(LovType::TicketPriority, $teamId))
                            ->default(fn () => Lov::getDefault(LovType::TicketPriority, $teamId)?->value ?? 'medium')
                            ->required(),
                        Select::make('assignee_id')
                            ->label('Assignee')
                            ->options(fn () => $this->team->users()->pluck('name', 'users.id'))
                            ->searchable(),
                        TextInput::make('story_points')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                    ]),
            ])
            ->action(function (array $data): void {
                $project = Project::find($data['project_id']);
                $ticketCount = Ticket::where('project_id', $project->id)->count() + 1;

                Ticket::create([
                    'team_id' => $this->team->id,
                    'project_id' => $data['project_id'],
                    'key' => $project->key . '-' . $ticketCount,
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'type' => $data['type'],
                    'status' => $data['status'],
                    'priority' => $data['priority'],
                    'assignee_id' => $data['assignee_id'] ?? null,
                    'reporter_id' => auth()->id(),
                    'story_points' => $data['story_points'] ?? null,
                ]);

                unset($this->ticketsByStatus);

                Notification::make()
                    ->title('Ticket created')
                    ->success()
                    ->send();
            });
    }

    public function createProjectAction(): Action
    {
        return Action::make('createProject')
            ->label('Project')
            ->icon(Heroicon::OutlinedFolder)
            ->modalHeading('Create Project')
            ->modalWidth('lg')
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('key')
                    ->required()
                    ->maxLength(10)
                    ->helperText('Short identifier (e.g., PROJ)')
                    ->dehydrateStateUsing(fn ($state) => Str::upper($state)),
                Textarea::make('description')
                    ->rows(3),
            ])
            ->action(function (array $data): void {
                Project::create([
                    'team_id' => $this->team->id,
                    'name' => $data['name'],
                    'key' => Str::upper($data['key']),
                    'description' => $data['description'] ?? null,
                    'lead_user_id' => auth()->id(),
                ]);

                unset($this->projects);

                Notification::make()
                    ->title('Project created')
                    ->success()
                    ->send();
            });
    }

    public function createEpicAction(): Action
    {
        return Action::make('createEpic')
            ->label('Epic')
            ->icon(Heroicon::OutlinedBolt)
            ->modalHeading('Create Epic')
            ->modalWidth('lg')
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Select::make('project_id')
                    ->label('Project')
                    ->options(fn () => Project::where('team_id', $this->team->id)->pluck('name', 'id'))
                    ->required()
                    ->default($this->projectId)
                    ->searchable(),
                Textarea::make('description')
                    ->rows(3),
                DatePicker::make('start_date'),
                DatePicker::make('end_date'),
            ])
            ->action(function (array $data): void {
                $project = Project::find($data['project_id']);
                $epicCount = Epic::where('project_id', $project->id)->count() + 1;

                Epic::create([
                    'team_id' => $this->team->id,
                    'project_id' => $data['project_id'],
                    'key' => $project->key . '-E' . $epicCount,
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'start_date' => $data['start_date'] ?? null,
                    'end_date' => $data['end_date'] ?? null,
                ]);

                unset($this->epics);

                Notification::make()
                    ->title('Epic created')
                    ->success()
                    ->send();
            });
    }

    public function editTicketAction(): Action
    {
        $teamId = $this->team->id;

        return Action::make('editTicket')
            ->modalHeading(fn () => 'Edit Ticket')
            ->modalWidth('4xl')
            ->fillForm(function (): array {
                $ticket = Ticket::find($this->editingTicketId);

                return [
                    'title' => $ticket->title,
                    'description' => $ticket->description,
                    'project_id' => $ticket->project_id,
                    'type' => $ticket->type,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'assignee_id' => $ticket->assignee_id,
                    'story_points' => $ticket->story_points,
                    'due_date' => $ticket->due_date,
                    'labels' => $ticket->labels ?? [],
                ];
            })
            ->schema([
                Section::make('Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        RichEditor::make('description')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bulletList',
                                'orderedList',
                                'link',
                            ]),
                        Select::make('project_id')
                            ->label('Project')
                            ->options(fn () => Project::where('team_id', $teamId)->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Select::make('type')
                            ->options(fn () => Lov::getOptions(LovType::TicketType, $teamId))
                            ->required(),
                        Select::make('status')
                            ->options(fn () => Lov::getOptions(LovType::TicketStatus, $teamId))
                            ->required(),
                        Select::make('priority')
                            ->options(fn () => Lov::getOptions(LovType::TicketPriority, $teamId))
                            ->required(),
                        Select::make('assignee_id')
                            ->label('Assignee')
                            ->options(fn () => $this->team->users()->pluck('name', 'users.id'))
                            ->searchable(),
                        TextInput::make('story_points')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                        DatePicker::make('due_date'),
                        TagsInput::make('labels')
                            ->placeholder('Add labels'),
                    ]),
            ])
            ->action(function (array $data): void {
                $ticket = Ticket::find($this->editingTicketId);
                $ticket->update([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'project_id' => $data['project_id'],
                    'type' => $data['type'],
                    'status' => $data['status'],
                    'priority' => $data['priority'],
                    'assignee_id' => $data['assignee_id'] ?? null,
                    'story_points' => $data['story_points'] ?? null,
                    'due_date' => $data['due_date'] ?? null,
                    'labels' => $data['labels'] ?? [],
                ]);

                unset($this->ticketsByStatus);
                $this->editingTicketId = null;

                Notification::make()
                    ->title('Ticket updated')
                    ->success()
                    ->send();
            })
            ->after(function () {
                $this->editingTicketId = null;
            });
    }

    public function getLovColor(string $color): string
    {
        return match ($color) {
            'danger' => '#dc2626',
            'success' => '#16a34a',
            'info' => '#2563eb',
            'warning' => '#d97706',
            'purple' => '#9333ea',
            'pink' => '#db2777',
            default => '#6b7280',
        };
    }

    public function getStatusHeaderBgColor(string $color): string
    {
        return match ($color) {
            'danger' => '#fee2e2',
            'success' => '#d1fae5',
            'info' => '#dbeafe',
            'warning' => '#fef3c7',
            'purple' => '#ede9fe',
            'pink' => '#fce7f3',
            default => '#e5e7eb',
        };
    }

    public function getTypeLov(string $value): ?Lov
    {
        return $this->types->firstWhere('value', $value);
    }

    public function getPriorityLov(string $value): ?Lov
    {
        return $this->priorities->firstWhere('value', $value);
    }
};
?>

<div>
    {{-- Filter Bar --}}
    <div style="margin-bottom: 16px; padding: 16px; background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            {{-- Filters --}}
            <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                {{-- Project Filter --}}
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label for="project-filter" style="font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                        Project
                    </label>
                    <select
                        id="project-filter"
                        wire:model.live="projectId"
                        style="padding: 8px 32px 8px 12px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; background: white; min-width: 160px; cursor: pointer;"
                    >
                        <option value="">All Projects</option>
                        @foreach ($this->projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Epic Filter --}}
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label for="epic-filter" style="font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                        Epic
                    </label>
                    <select
                        id="epic-filter"
                        wire:model.live="epicId"
                        style="padding: 8px 32px 8px 12px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; background: white; min-width: 160px; cursor: pointer;"
                    >
                        <option value="">All Epics</option>
                        @foreach ($this->epics as $epic)
                            <option value="{{ $epic->id }}">{{ $epic->title }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Assignee Filter --}}
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label for="assignee-filter" style="font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                        Assignee
                    </label>
                    <select
                        id="assignee-filter"
                        wire:model.live="assigneeId"
                        style="padding: 8px 32px 8px 12px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; background: white; min-width: 160px; cursor: pointer;"
                    >
                        <option value="">All Assignees</option>
                        @foreach ($this->teamUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Clear Filters --}}
                @if ($this->activeFilterCount > 0)
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        <span style="font-size: 11px; font-weight: 600; color: transparent;">Clear</span>
                        <button
                            wire:click="clearFilters"
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; background: #fee2e2; color: #dc2626; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; cursor: pointer;"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                            Clear ({{ $this->activeFilterCount }})
                        </button>
                    </div>
                @endif
            </div>

            {{-- New Button Dropdown --}}
            <div x-data="{ open: false }" style="position: relative;">
                <button
                    @click="open = !open"
                    @click.outside="open = false"
                    style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: #2563eb; color: white; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; cursor: pointer;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 12px; height: 12px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div
                    x-show="open"
                    x-transition
                    style="position: absolute; right: 0; top: 100%; margin-top: 4px; background: white; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; min-width: 160px; z-index: 50;"
                >
                    <button
                        wire:click="mountAction('createTicket')"
                        @click="open = false"
                        style="display: flex; align-items: center; gap: 8px; width: 100%; padding: 10px 16px; font-size: 14px; text-align: left; border: none; background: none; cursor: pointer; color: #374151;"
                        onmouseover="this.style.background='#f3f4f6'"
                        onmouseout="this.style.background='none'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 18px; height: 18px; color: #6b7280;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                        </svg>
                        Ticket
                    </button>
                    <button
                        wire:click="mountAction('createEpic')"
                        @click="open = false"
                        style="display: flex; align-items: center; gap: 8px; width: 100%; padding: 10px 16px; font-size: 14px; text-align: left; border: none; background: none; cursor: pointer; color: #374151;"
                        onmouseover="this.style.background='#f3f4f6'"
                        onmouseout="this.style.background='none'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 18px; height: 18px; color: #6b7280;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                        </svg>
                        Epic
                    </button>
                    <button
                        wire:click="mountAction('createProject')"
                        @click="open = false"
                        style="display: flex; align-items: center; gap: 8px; width: 100%; padding: 10px 16px; font-size: 14px; text-align: left; border: none; background: none; cursor: pointer; color: #374151;"
                        onmouseover="this.style.background='#f3f4f6'"
                        onmouseout="this.style.background='none'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 18px; height: 18px; color: #6b7280;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                        </svg>
                        Project
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Kanban Board --}}
    <div style="display: flex; gap: 16px; overflow-x: auto; padding-bottom: 16px; min-height: 70vh;">
        @foreach ($this->statuses as $status)
            <div style="flex-shrink: 0; width: 280px;">
                {{-- Column Header --}}
                <div style="background: {{ $this->getStatusHeaderBgColor($status->color ?? 'gray') }}; border-radius: 8px 8px 0 0; padding: 12px; border: 1px solid #e5e7eb; border-bottom: none;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            @if ($status->icon)
                                <x-filament::icon
                                    :icon="$status->icon"
                                    style="width: 16px; height: 16px; color: {{ $this->getLovColor($status->color ?? 'gray') }};"
                                />
                            @endif
                            <h3 style="font-weight: 600; font-size: 14px; color: #111827; margin: 0;">
                                {{ $status->name }}
                            </h3>
                        </div>
                        <span style="background: white; padding: 2px 8px; border-radius: 9999px; font-size: 12px; font-weight: 500; color: #6b7280;">
                            {{ count($this->ticketsByStatus[$status->value] ?? []) }}
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
                    @forelse ($this->ticketsByStatus[$status->value] ?? [] as $ticket)
                        @php
                            $typeLov = $this->getTypeLov($ticket->type);
                            $priorityLov = $this->getPriorityLov($ticket->priority);
                        @endphp
                        <div
                            wire:key="ticket-{{ $ticket->id }}"
                            wire:sort:item="{{ $ticket->id }}"
                            wire:dblclick="openEditModal({{ $ticket->id }})"
                            style="background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; padding: 12px; margin-bottom: 8px; cursor: grab; user-select: none;"
                            title="Double-click to edit"
                        >
                            {{-- Ticket Header --}}
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                                <div style="display: flex; align-items: center; gap: 6px; min-width: 0;">
                                    @if ($typeLov?->icon)
                                        <x-filament::icon
                                            :icon="$typeLov->icon"
                                            style="width: 16px; height: 16px; color: {{ $this->getLovColor($typeLov->color ?? 'gray') }};"
                                        />
                                    @endif
                                    <span style="font-size: 12px; font-weight: 500; color: #6b7280;">
                                        {{ $ticket->key }}
                                    </span>
                                </div>
                                @if ($priorityLov?->icon)
                                    <x-filament::icon
                                        :icon="$priorityLov->icon"
                                        style="width: 16px; height: 16px; flex-shrink: 0; color: {{ $this->getLovColor($priorityLov->color ?? 'gray') }};"
                                    />
                                @endif
                            </div>

                            {{-- Ticket Title --}}
                            <p style="font-size: 14px; font-weight: 500; color: #111827; margin: 0 0 8px 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                {{ $ticket->title }}
                            </p>

                            {{-- Epic Badge --}}
                            @if ($ticket->epic)
                                <div style="margin-bottom: 8px;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; background: #fef3c7; color: #92400e;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 10px; height: 10px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                                        </svg>
                                        {{ Str::limit($ticket->epic->title, 20) }}
                                    </span>
                                </div>
                            @endif

                            {{-- Ticket Footer --}}
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span style="font-size: 11px; color: #6b7280; font-weight: 500;">
                                    {{ $ticket->project?->key ?? 'No Project' }}
                                </span>
                                @if ($ticket->assignee)
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: #dbeafe; display: flex; align-items: center; justify-content: center;" title="{{ $ticket->assignee->name }}">
                                        <span style="font-size: 10px; font-weight: 600; color: #1d4ed8;">
                                            {{ $ticket->assignee->initials() }}
                                        </span>
                                    </div>
                                @else
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: #f3f4f6; display: flex; align-items: center; justify-content: center;" title="Unassigned">
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

    {{-- Filament Actions Modals --}}
    <x-filament-actions::modals />
</div>
