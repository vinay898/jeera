<?php

use App\Enums\EpicStatus;
use App\Enums\LovType;
use App\Enums\TeamRole;
use App\Enums\TicketStatus;
use App\Filament\Actions\TicketActions;
use App\Filament\Pages\TeamSettings;
use App\Filament\Resources\Epics\EpicResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Mail\TeamInvitationMail;
use App\Models\Epic;
use App\Models\Lov;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TeamInvitation;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    #[Url]
    public array $projectIds = [];

    #[Url]
    public array $epicIds = [];

    #[Url]
    public array $assigneeIds = [];

    #[Url]
    public array $categoryIds = [];

    #[Url]
    public array $labelIds = [];

    public ?int $editingTicketId = null;

    public ?int $editingLovId = null;

    public ?int $deletingLovId = null;

    public ?int $editingProjectId = null;

    public ?int $deletingProjectId = null;

    public ?int $editingEpicId = null;

    public ?int $deletingEpicId = null;

    public function mount(?int $projectId = null): void
    {
        $this->projectIds = $projectId ? [$projectId] : [];
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

        if (! empty($this->projectIds)) {
            $query->whereIn('project_id', $this->projectIds);
        }

        return $query->orderBy('title')->get();
    }

    #[Computed]
    public function teamUsers(): \Illuminate\Support\Collection
    {
        $project = $this->singleSelectedProject();

        if ($project) {
            $shortlist = $project->assignees()->orderBy('name')->get();

            if ($shortlist->isNotEmpty()) {
                return $shortlist;
            }
        }

        return $this->team->users()->orderBy('name')->get();
    }

    private function singleSelectedProject(): ?Project
    {
        if (count($this->projectIds) !== 1) {
            return null;
        }

        return Project::find($this->projectIds[0]);
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
    public function categories(): Collection
    {
        return Lov::getForTeam(LovType::TicketCategory, $this->team->id);
    }

    #[Computed]
    public function labels(): Collection
    {
        return Lov::getForTeam(LovType::TicketLabel, $this->team->id);
    }

    #[Computed]
    public function activeFilterCount(): int
    {
        $count = 0;
        if (! empty($this->projectIds)) {
            $count++;
        }
        if (! empty($this->epicIds)) {
            $count++;
        }
        if (! empty($this->assigneeIds)) {
            $count++;
        }
        if (! empty($this->categoryIds)) {
            $count++;
        }
        if (! empty($this->labelIds)) {
            $count++;
        }

        return $count;
    }

    #[Computed]
    public function ticketsByStatus(): array
    {
        $query = Ticket::with(['assignee', 'project', 'epic', 'categories', 'ticketLabels'])
            ->where('team_id', $this->team->id);

        if (! empty($this->projectIds)) {
            $query->whereIn('project_id', $this->projectIds);
        }

        if (! empty($this->epicIds)) {
            $query->whereIn('epic_id', $this->epicIds);
        }

        if (! empty($this->assigneeIds)) {
            $query->whereIn('assignee_id', $this->assigneeIds);
        }

        if (! empty($this->categoryIds)) {
            $query->whereHas('categories', fn ($q) => $q->whereIn('lovs.id', $this->categoryIds));
        }

        if (! empty($this->labelIds)) {
            $query->whereHas('ticketLabels', fn ($q) => $q->whereIn('lovs.id', $this->labelIds));
        }

        $tickets = $query->orderBy('priority')->get();

        $grouped = [];
        foreach ($this->statuses as $status) {
            $grouped[$status->value] = $tickets->filter(function ($ticket) use ($status) {
                $ticketStatus = $ticket->status instanceof \BackedEnum
                    ? $ticket->status->value
                    : $ticket->status;

                return $ticketStatus === $status->value;
            })->values();
        }

        return $grouped;
    }

    public function updatedProjectIds(): void
    {
        // Reset epic and assignee filters when project selection changes
        $this->epicIds = [];
        $this->assigneeIds = [];
        unset($this->epics);
        unset($this->teamUsers);
        unset($this->ticketsByStatus);
    }

    public function updatedEpicIds(): void
    {
        unset($this->ticketsByStatus);
    }

    public function updatedAssigneeIds(): void
    {
        unset($this->ticketsByStatus);
    }

    public function updatedCategoryIds(): void
    {
        unset($this->ticketsByStatus);
    }

    public function updatedLabelIds(): void
    {
        unset($this->ticketsByStatus);
    }

    public function clearFilters(): void
    {
        $this->projectIds = [];
        $this->epicIds = [];
        $this->assigneeIds = [];
        $this->categoryIds = [];
        $this->labelIds = [];
        unset($this->epics);
        unset($this->ticketsByStatus);
    }

    public function handleSort(int $ticketId, int $position, string $statusValue): void
    {
        $ticket = Ticket::where('team_id', $this->team->id)->findOrFail($ticketId);

        if ($statusValue === TicketStatus::Done->value) {
            $unresolvedParent = Ticket::findUnresolvedParent($ticket->parent_id);

            if ($unresolvedParent) {
                Notification::make()
                    ->title("Parent ticket: {$unresolvedParent->title} is not done")
                    ->danger()
                    ->send();

                unset($this->ticketsByStatus);

                return;
            }
        }

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
        return TicketActions::create(
            count($this->projectIds) === 1 ? $this->projectIds[0] : null,
            function () {
                unset($this->ticketsByStatus);
            }
        );
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
                    ->default(count($this->projectIds) === 1 ? $this->projectIds[0] : null)
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

    public function openEditProject(int $id): void
    {
        $this->editingProjectId = $id;
        $this->mountAction('editProject');
    }

    public function editProjectAction(): Action
    {
        return Action::make('editProject')
            ->modalHeading('Edit Project')
            ->modalWidth('sm')
            ->fillForm(function (): array {
                $project = Project::find($this->editingProjectId);

                return $project ? [
                    'name' => $project->name,
                    'key' => $project->key,
                    'is_archived' => $project->is_archived,
                ] : [];
            })
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('key')
                    ->required()
                    ->maxLength(10)
                    ->rules([
                        function (): \Closure {
                            return function (string $attribute, mixed $value, \Closure $fail): void {
                                $exists = Project::where('team_id', $this->team->id)
                                    ->where('key', Str::upper($value))
                                    ->where('id', '!=', $this->editingProjectId)
                                    ->exists();

                                if ($exists) {
                                    $fail('This key is already in use.');
                                }
                            };
                        },
                    ])
                    ->dehydrateStateUsing(fn ($state) => Str::upper($state)),
                Toggle::make('is_archived')
                    ->label('Archived')
                    ->helperText('Archived projects are hidden from lists'),
            ])
            ->modalSubmitActionLabel('Save')
            ->extraModalFooterActions([
                Action::make('advancedProject')
                    ->label('Advanced settings')
                    ->link()
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn () => ProjectResource::getUrl('edit', ['record' => $this->editingProjectId])),
            ])
            ->action(function (array $data): void {
                $project = Project::find($this->editingProjectId);

                if (! $project) {
                    return;
                }

                $project->update([
                    'name' => $data['name'],
                    'key' => Str::upper($data['key']),
                    'is_archived' => $data['is_archived'] ?? false,
                ]);

                unset($this->projects);

                Notification::make()
                    ->title('Project updated')
                    ->success()
                    ->send();
            })
            ->after(fn () => $this->editingProjectId = null);
    }

    public function confirmDeleteProject(int $id): void
    {
        $this->deletingProjectId = $id;
        $this->mountAction('deleteProject');
    }

    public function deleteProjectAction(): Action
    {
        return Action::make('deleteProject')
            ->requiresConfirmation()
            ->color('danger')
            ->modalHeading('Delete this project?')
            ->modalDescription('This cannot be undone.')
            ->modalSubmitActionLabel('Delete')
            ->action(function (): void {
                $project = Project::find($this->deletingProjectId);

                if (! $project) {
                    return;
                }

                $inUse = Ticket::where('project_id', $project->id)->exists()
                    || Epic::where('project_id', $project->id)->exists();

                if ($inUse) {
                    Notification::make()
                        ->title("Can't delete \"{$project->name}\" — it still has tickets or epics")
                        ->danger()
                        ->send();

                    return;
                }

                $project->delete();

                $this->projectIds = array_values(array_diff($this->projectIds, [$project->id]));

                unset($this->projects);
                unset($this->ticketsByStatus);

                Notification::make()
                    ->title('Project deleted')
                    ->success()
                    ->send();
            })
            ->after(fn () => $this->deletingProjectId = null);
    }

    public function openEditEpic(int $id): void
    {
        $this->editingEpicId = $id;
        $this->mountAction('editEpic');
    }

    public function editEpicAction(): Action
    {
        return Action::make('editEpic')
            ->modalHeading('Edit Epic')
            ->modalWidth('sm')
            ->fillForm(function (): array {
                $epic = Epic::find($this->editingEpicId);

                return $epic ? [
                    'title' => $epic->title,
                    'status' => $epic->status instanceof \BackedEnum ? $epic->status->value : $epic->status,
                ] : [];
            })
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Select::make('status')
                    ->options(EpicStatus::class)
                    ->required()
                    ->native(false),
            ])
            ->modalSubmitActionLabel('Save')
            ->extraModalFooterActions([
                Action::make('advancedEpic')
                    ->label('Advanced settings')
                    ->link()
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn () => EpicResource::getUrl('edit', ['record' => $this->editingEpicId])),
            ])
            ->action(function (array $data): void {
                $epic = Epic::find($this->editingEpicId);

                if (! $epic) {
                    return;
                }

                $epic->update([
                    'title' => $data['title'],
                    'status' => $data['status'],
                ]);

                unset($this->epics);

                Notification::make()
                    ->title('Epic updated')
                    ->success()
                    ->send();
            })
            ->after(fn () => $this->editingEpicId = null);
    }

    public function confirmDeleteEpic(int $id): void
    {
        $this->deletingEpicId = $id;
        $this->mountAction('deleteEpic');
    }

    public function deleteEpicAction(): Action
    {
        return Action::make('deleteEpic')
            ->requiresConfirmation()
            ->color('danger')
            ->modalHeading('Delete this epic?')
            ->modalDescription('This cannot be undone.')
            ->modalSubmitActionLabel('Delete')
            ->action(function (): void {
                $epic = Epic::find($this->deletingEpicId);

                if (! $epic) {
                    return;
                }

                $inUse = Ticket::where('epic_id', $epic->id)->exists();

                if ($inUse) {
                    Notification::make()
                        ->title("Can't delete \"{$epic->title}\" — it still has tickets")
                        ->danger()
                        ->send();

                    return;
                }

                $epic->delete();

                $this->epicIds = array_values(array_diff($this->epicIds, [$epic->id]));

                unset($this->epics);
                unset($this->ticketsByStatus);

                Notification::make()
                    ->title('Epic deleted')
                    ->success()
                    ->send();
            })
            ->after(fn () => $this->deletingEpicId = null);
    }

    public function manageProjectAssigneesAction(): Action
    {
        return Action::make('manageProjectAssignees')
            ->modalHeading('Manage Assignees')
            ->modalWidth('sm')
            ->fillForm(function (): array {
                $project = $this->singleSelectedProject();

                return $project ? [
                    'user_ids' => $project->assignees()->pluck('users.id')->all(),
                ] : [];
            })
            ->schema([
                CheckboxList::make('user_ids')
                    ->label('Team members')
                    ->options(fn () => $this->team->users()->orderBy('name')->pluck('name', 'id')),
            ])
            ->modalSubmitActionLabel('Save')
            ->extraModalFooterActions([
                Action::make('inviteFromManageAssignees')
                    ->label('Invite a new teammate')
                    ->link()
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->url(fn () => TeamSettings::getUrl()),
            ])
            ->action(function (array $data): void {
                $project = $this->singleSelectedProject();

                if (! $project) {
                    return;
                }

                $project->assignees()->sync($data['user_ids'] ?? []);

                unset($this->teamUsers);

                Notification::make()
                    ->title('Assignees updated')
                    ->success()
                    ->send();
            });
    }

    public function inviteAssigneeAction(): Action
    {
        return Action::make('inviteAssignee')
            ->modalHeading('Invite Teammate')
            ->modalWidth('sm')
            ->schema([
                TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->rules([
                        function (): \Closure {
                            return function (string $attribute, mixed $value, \Closure $fail): void {
                                if ($this->team->users()->where('email', $value)->exists()) {
                                    $fail('This user is already a member of this team.');
                                }

                                if ($this->team->pendingInvitations()->where('email', $value)->exists()) {
                                    $fail('An invitation is already pending for this email.');
                                }
                            };
                        },
                    ]),
                Select::make('role')
                    ->label('Role')
                    ->options([
                        TeamRole::Admin->value => TeamRole::Admin->label().' - '.TeamRole::Admin->description(),
                        TeamRole::Member->value => TeamRole::Member->label().' - '.TeamRole::Member->description(),
                    ])
                    ->default(TeamRole::Member->value)
                    ->required()
                    ->native(false),
            ])
            ->modalSubmitActionLabel('Send Invite')
            ->action(function (array $data): void {
                $invitation = TeamInvitation::create([
                    'team_id' => $this->team->id,
                    'email' => $data['email'],
                    'role' => $data['role'],
                    'token' => TeamInvitation::generateToken(),
                    'expires_at' => now()->addDays(7),
                    'invited_by' => auth()->id(),
                ]);

                Mail::to($data['email'])->send(new TeamInvitationMail($invitation));

                Notification::make()
                    ->title('Invitation sent')
                    ->body("An invitation has been sent to {$data['email']}")
                    ->success()
                    ->send();
            });
    }

    public function createCategoryAction(): Action
    {
        return $this->createLovAction(LovType::TicketCategory, 'createCategory', 'Category', Heroicon::OutlinedFolder);
    }

    public function createLabelAction(): Action
    {
        return $this->createLovAction(LovType::TicketLabel, 'createLabel', 'Label', Heroicon::OutlinedBookmark);
    }

    private function createLovAction(LovType $type, string $name, string $label, Heroicon $icon): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->modalHeading("Create {$label}")
            ->modalWidth('sm')
            ->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->rules([
                        function () use ($type, $label): \Closure {
                            return function (string $attribute, mixed $value, \Closure $fail) use ($type, $label): void {
                                $exists = Lov::query()
                                    ->forTeam($this->team->id)
                                    ->ofType($type)
                                    ->where('value', Str::slug($value))
                                    ->exists();

                                if ($exists) {
                                    $fail("A {$label} named \"{$value}\" already exists.");
                                }
                            };
                        },
                    ]),
            ])
            ->modalSubmitActionLabel("Create {$label}")
            ->action(function (array $data) use ($type, $label): void {
                Lov::create([
                    'team_id' => $this->team->id,
                    'type' => $type,
                    'name' => $data['name'],
                    'value' => Str::slug($data['name']),
                    'is_active' => true,
                ]);

                unset($this->categories);
                unset($this->labels);

                Notification::make()
                    ->title("{$label} created")
                    ->success()
                    ->send();
            });
    }

    public function openEditLov(int $id): void
    {
        $this->editingLovId = $id;
        $this->mountAction('editLov');
    }

    public function editLovAction(): Action
    {
        return Action::make('editLov')
            ->modalHeading('Edit')
            ->modalWidth('sm')
            ->fillForm(function (): array {
                $lov = Lov::find($this->editingLovId);

                return $lov ? [
                    'name' => $lov->name,
                    'color' => $lov->color,
                    'is_active' => $lov->is_active,
                ] : [];
            })
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('color')
                    ->options([
                        'gray' => 'Gray',
                        'danger' => 'Red',
                        'warning' => 'Orange',
                        'success' => 'Green',
                        'info' => 'Blue',
                        'purple' => 'Purple',
                        'pink' => 'Pink',
                    ])
                    ->native(false),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ])
            ->modalSubmitActionLabel('Save')
            ->action(function (array $data): void {
                $lov = Lov::find($this->editingLovId);

                if (! $lov) {
                    return;
                }

                $lov->update([
                    'name' => $data['name'],
                    'color' => $data['color'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                ]);

                unset($this->categories);
                unset($this->labels);

                Notification::make()
                    ->title('Updated')
                    ->success()
                    ->send();
            })
            ->after(fn () => $this->editingLovId = null);
    }

    public function confirmDeleteLov(int $id): void
    {
        $this->deletingLovId = $id;
        $this->mountAction('deleteLov');
    }

    public function deleteLovAction(): Action
    {
        return Action::make('deleteLov')
            ->requiresConfirmation()
            ->color('danger')
            ->modalHeading('Delete this item?')
            ->modalDescription('This cannot be undone.')
            ->modalSubmitActionLabel('Delete')
            ->action(function (): void {
                $lov = Lov::find($this->deletingLovId);

                if (! $lov) {
                    return;
                }

                $inUse = $lov->type === LovType::TicketCategory
                    ? Ticket::whereHas('categories', fn ($q) => $q->where('lovs.id', $lov->id))->exists()
                    : Ticket::whereHas('ticketLabels', fn ($q) => $q->where('lovs.id', $lov->id))->exists();

                if ($inUse) {
                    Notification::make()
                        ->title("Can't delete \"{$lov->name}\" — it's still used on tickets")
                        ->danger()
                        ->send();

                    return;
                }

                $lov->delete();

                unset($this->categories);
                unset($this->labels);

                Notification::make()
                    ->title('Deleted')
                    ->success()
                    ->send();
            })
            ->after(fn () => $this->deletingLovId = null);
    }

    private function reorderLovsOfType(LovType $type, int $lovId, int $position): void
    {
        $ids = Lov::query()
            ->forTeam($this->team->id)
            ->ofType($type)
            ->ordered()
            ->pluck('id')
            ->all();

        $ids = array_values(array_filter($ids, fn (int $id) => $id !== $lovId));
        array_splice($ids, $position, 0, [$lovId]);

        foreach ($ids as $index => $id) {
            Lov::where('id', $id)->update(['sort_order' => $index]);
        }

        unset($this->categories);
        unset($this->labels);
    }

    public function reorderCategories(int $lovId, int $position): void
    {
        $this->reorderLovsOfType(LovType::TicketCategory, $lovId, $position);
    }

    public function reorderLabels(int $lovId, int $position): void
    {
        $this->reorderLovsOfType(LovType::TicketLabel, $lovId, $position);
    }

    public function editTicketAction(): Action
    {
        return TicketActions::edit(
            fn () => $this->editingTicketId,
            function () {
                unset($this->ticketsByStatus);
                $this->editingTicketId = null;
            }
        )->after(fn () => $this->editingTicketId = null);
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

    public function getTypeLov(string|\BackedEnum $value): ?Lov
    {
        $stringValue = $value instanceof \BackedEnum ? $value->value : $value;

        return $this->types->firstWhere('value', $stringValue);
    }

    public function getPriorityLov(string|\BackedEnum $value): ?Lov
    {
        $stringValue = $value instanceof \BackedEnum ? $value->value : $value;

        return $this->priorities->firstWhere('value', $stringValue);
    }
};
?>

<div>
    <style>
        .kanban-filter-select option {
            padding: 6px 10px;
        }
    </style>

    {{-- Filter Bar --}}
    <div style="margin-bottom: 16px; padding: 16px; background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            {{-- Filters --}}
            <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                {{-- Project Filter --}}
                <div x-data="{ manageOpen: false }" style="display: flex; flex-direction: column; gap: 4px; position: relative;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                        <label for="project-filter" style="font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                            Project {{ count($this->projectIds) ? '(' . count($this->projectIds) . ')' : '' }}
                        </label>
                        <button
                            type="button"
                            @click="manageOpen = !manageOpen"
                            title="Manage projects"
                            style="display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; border: none; background: none; cursor: pointer; color: #6b7280; padding: 0;"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.02-.397-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </button>
                    </div>
                    <select
                        id="project-filter"
                        class="kanban-filter-select"
                        wire:model.live="projectIds"
                        multiple
                        size="1"
                        style="padding: 8px 12px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; background: white; min-width: 160px; cursor: pointer;"
                    >
                        @foreach ($this->projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>

                    <div
                        x-show="manageOpen"
                        x-transition
                        @click.outside="manageOpen = false"
                        style="display: none; position: absolute; top: 100%; left: 0; margin-top: 4px; width: 280px; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1); padding: 8px; z-index: 30;"
                    >
                        <button
                            type="button"
                            @click="manageOpen = false"
                            wire:click="mountAction('createProject')"
                            style="display: flex; align-items: center; gap: 6px; width: 100%; padding: 6px 8px; margin-bottom: 6px; border: 1px dashed #93c5fd; border-radius: 6px; background: none; cursor: pointer; color: #2563eb; font-size: 13px; font-weight: 500;"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add project
                        </button>
                        <div style="display: flex; flex-direction: column; gap: 6px; max-height: 300px; overflow-y: auto;">
                            @forelse ($this->projects as $project)
                                <div
                                    wire:key="manage-project-{{ $project->id }}"
                                    style="display: flex; align-items: center; gap: 8px; padding: 6px 8px; border: 1px solid #e5e7eb; border-radius: 6px; background: white;"
                                >
                                    <span style="flex: 1; min-width: 0;">
                                        <span style="font-size: 13px; color: {{ $project->is_archived ? '#9ca3af' : '#111827' }}; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $project->name }}
                                        </span>
                                        <span style="font-size: 11px; color: #9ca3af;">
                                            {{ $project->key }}
                                            @if ($project->is_archived)
                                                &middot; archived
                                            @endif
                                        </span>
                                    </span>
                                    <button type="button" @click="manageOpen = false" wire:click="openEditProject({{ $project->id }})" title="Edit" style="display: inline-flex; border: none; background: none; cursor: pointer; color: #6b7280; padding: 2px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 14px; height: 14px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="manageOpen = false" wire:click="confirmDeleteProject({{ $project->id }})" title="Delete" style="display: inline-flex; border: none; background: none; cursor: pointer; color: #dc2626; padding: 2px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 14px; height: 14px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            @empty
                                <div style="text-align: center; padding: 16px 0; color: #9ca3af; font-size: 13px;">
                                    None yet.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Epic Filter --}}
                <div x-data="{ manageOpen: false }" style="display: flex; flex-direction: column; gap: 4px; position: relative;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                        <label for="epic-filter" style="font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                            Epic {{ count($this->epicIds) ? '(' . count($this->epicIds) . ')' : '' }}
                        </label>
                        <button
                            type="button"
                            @click="manageOpen = !manageOpen"
                            title="Manage epics"
                            style="display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; border: none; background: none; cursor: pointer; color: #6b7280; padding: 0;"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.02-.397-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </button>
                    </div>
                    <select
                        id="epic-filter"
                        class="kanban-filter-select"
                        wire:model.live="epicIds"
                        multiple
                        size="1"
                        style="padding: 8px 12px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; background: white; min-width: 160px; cursor: pointer;"
                    >
                        @foreach ($this->epics as $epic)
                            <option value="{{ $epic->id }}">{{ $epic->title }}</option>
                        @endforeach
                    </select>

                    <div
                        x-show="manageOpen"
                        x-transition
                        @click.outside="manageOpen = false"
                        style="display: none; position: absolute; top: 100%; left: 0; margin-top: 4px; width: 280px; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1); padding: 8px; z-index: 30;"
                    >
                        <button
                            type="button"
                            @click="manageOpen = false"
                            wire:click="mountAction('createEpic')"
                            style="display: flex; align-items: center; gap: 6px; width: 100%; padding: 6px 8px; margin-bottom: 6px; border: 1px dashed #93c5fd; border-radius: 6px; background: none; cursor: pointer; color: #2563eb; font-size: 13px; font-weight: 500;"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add epic
                        </button>
                        <div style="display: flex; flex-direction: column; gap: 6px; max-height: 300px; overflow-y: auto;">
                            @forelse ($this->epics as $epic)
                                <div
                                    wire:key="manage-epic-{{ $epic->id }}"
                                    style="display: flex; align-items: center; gap: 8px; padding: 6px 8px; border: 1px solid #e5e7eb; border-radius: 6px; background: white;"
                                >
                                    <span style="flex: 1; min-width: 0;">
                                        <span style="font-size: 13px; color: #111827; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $epic->title }}
                                        </span>
                                        <span style="font-size: 11px; color: #9ca3af;">
                                            {{ $epic->status instanceof \App\Enums\EpicStatus ? $epic->status->label() : $epic->status }}
                                        </span>
                                    </span>
                                    <button type="button" @click="manageOpen = false" wire:click="openEditEpic({{ $epic->id }})" title="Edit" style="display: inline-flex; border: none; background: none; cursor: pointer; color: #6b7280; padding: 2px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 14px; height: 14px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="manageOpen = false" wire:click="confirmDeleteEpic({{ $epic->id }})" title="Delete" style="display: inline-flex; border: none; background: none; cursor: pointer; color: #dc2626; padding: 2px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 14px; height: 14px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            @empty
                                <div style="text-align: center; padding: 16px 0; color: #9ca3af; font-size: 13px;">
                                    None yet.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Assignee Filter --}}
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                        <label for="assignee-filter" style="font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                            Assignee {{ count($this->assigneeIds) ? '(' . count($this->assigneeIds) . ')' : '' }}
                        </label>
                        @if (count($this->projectIds) === 1)
                            <button
                                type="button"
                                wire:click="mountAction('manageProjectAssignees')"
                                title="Manage assignees for this project"
                                style="display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; border: none; background: none; cursor: pointer; color: #6b7280; padding: 0;"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" style="width: 14px; height: 14px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.02-.397-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                            </button>
                        @else
                            <a
                                href="{{ TeamSettings::getUrl() }}"
                                title="Manage team members"
                                style="display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; color: #6b7280;"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" style="width: 14px; height: 14px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.02-.397-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                            </a>
                        @endif
                    </div>
                    <select
                        id="assignee-filter"
                        class="kanban-filter-select"
                        wire:model.live="assigneeIds"
                        multiple
                        size="1"
                        style="padding: 8px 12px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; background: white; min-width: 160px; cursor: pointer;"
                    >
                        @foreach ($this->teamUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Category Filter --}}
                <div x-data="{ manageOpen: false }" style="display: flex; flex-direction: column; gap: 4px; position: relative;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                        <label for="category-filter" style="font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                            Category {{ count($this->categoryIds) ? '(' . count($this->categoryIds) . ')' : '' }}
                        </label>
                        <button
                            type="button"
                            @click="manageOpen = !manageOpen"
                            title="Manage categories"
                            style="display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; border: none; background: none; cursor: pointer; color: #6b7280; padding: 0;"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.02-.397-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </button>
                    </div>
                    <select
                        id="category-filter"
                        class="kanban-filter-select"
                        wire:model.live="categoryIds"
                        multiple
                        size="1"
                        style="padding: 8px 12px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; background: white; min-width: 140px; cursor: pointer;"
                    >
                        @foreach ($this->categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>

                    <div
                        x-show="manageOpen"
                        x-transition
                        @click.outside="manageOpen = false"
                        style="display: none; position: absolute; top: 100%; left: 0; margin-top: 4px; width: 260px; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1); padding: 8px; z-index: 30;"
                    >
                        <button
                            type="button"
                            @click="manageOpen = false"
                            wire:click="mountAction('createCategory')"
                            style="display: flex; align-items: center; gap: 6px; width: 100%; padding: 6px 8px; margin-bottom: 6px; border: 1px dashed #93c5fd; border-radius: 6px; background: none; cursor: pointer; color: #2563eb; font-size: 13px; font-weight: 500;"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add category
                        </button>
                        <div wire:sort="reorderCategories" style="display: flex; flex-direction: column; gap: 6px; max-height: 300px; overflow-y: auto;">
                            @forelse ($this->categories as $category)
                                <div
                                    wire:key="manage-category-{{ $category->id }}"
                                    wire:sort:item="{{ $category->id }}"
                                    style="display: flex; align-items: center; gap: 8px; padding: 6px 8px; border: 1px solid #e5e7eb; border-radius: 6px; background: white; cursor: grab;"
                                >
                                    <span style="width: 9px; height: 9px; border-radius: 9999px; background: {{ $this->getLovColor($category->color ?? 'gray') }}; flex-shrink: 0;"></span>
                                    <span style="flex: 1; font-size: 13px; color: {{ $category->is_active ? '#111827' : '#9ca3af' }};">
                                        {{ $category->name }}
                                    </span>
                                    <button type="button" wire:sort:ignore @click="manageOpen = false" wire:click="openEditLov({{ $category->id }})" title="Edit" style="display: inline-flex; border: none; background: none; cursor: pointer; color: #6b7280; padding: 2px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 14px; height: 14px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                        </svg>
                                    </button>
                                    <button type="button" wire:sort:ignore @click="manageOpen = false" wire:click="confirmDeleteLov({{ $category->id }})" title="Delete" style="display: inline-flex; border: none; background: none; cursor: pointer; color: #dc2626; padding: 2px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 14px; height: 14px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            @empty
                                <div style="text-align: center; padding: 16px 0; color: #9ca3af; font-size: 13px;">
                                    None yet.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Label Filter --}}
                <div x-data="{ manageOpen: false }" style="display: flex; flex-direction: column; gap: 4px; position: relative;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                        <label for="label-filter" style="font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                            Label {{ count($this->labelIds) ? '(' . count($this->labelIds) . ')' : '' }}
                        </label>
                        <button
                            type="button"
                            @click="manageOpen = !manageOpen"
                            title="Manage labels"
                            style="display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; border: none; background: none; cursor: pointer; color: #6b7280; padding: 0;"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.02-.397-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </button>
                    </div>
                    <select
                        id="label-filter"
                        class="kanban-filter-select"
                        wire:model.live="labelIds"
                        multiple
                        size="1"
                        style="padding: 8px 12px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; background: white; min-width: 140px; cursor: pointer;"
                    >
                        @foreach ($this->labels as $label)
                            <option value="{{ $label->id }}">{{ $label->name }}</option>
                        @endforeach
                    </select>

                    <div
                        x-show="manageOpen"
                        x-transition
                        @click.outside="manageOpen = false"
                        style="display: none; position: absolute; top: 100%; left: 0; margin-top: 4px; width: 260px; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1); padding: 8px; z-index: 30;"
                    >
                        <button
                            type="button"
                            @click="manageOpen = false"
                            wire:click="mountAction('createLabel')"
                            style="display: flex; align-items: center; gap: 6px; width: 100%; padding: 6px 8px; margin-bottom: 6px; border: 1px dashed #93c5fd; border-radius: 6px; background: none; cursor: pointer; color: #2563eb; font-size: 13px; font-weight: 500;"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add label
                        </button>
                        <div wire:sort="reorderLabels" style="display: flex; flex-direction: column; gap: 6px; max-height: 300px; overflow-y: auto;">
                            @forelse ($this->labels as $label)
                                <div
                                    wire:key="manage-label-{{ $label->id }}"
                                    wire:sort:item="{{ $label->id }}"
                                    style="display: flex; align-items: center; gap: 8px; padding: 6px 8px; border: 1px solid #e5e7eb; border-radius: 6px; background: white; cursor: grab;"
                                >
                                    <span style="width: 9px; height: 9px; border-radius: 9999px; background: {{ $this->getLovColor($label->color ?? 'gray') }}; flex-shrink: 0;"></span>
                                    <span style="flex: 1; font-size: 13px; color: {{ $label->is_active ? '#111827' : '#9ca3af' }};">
                                        {{ $label->name }}
                                    </span>
                                    <button type="button" wire:sort:ignore @click="manageOpen = false" wire:click="openEditLov({{ $label->id }})" title="Edit" style="display: inline-flex; border: none; background: none; cursor: pointer; color: #6b7280; padding: 2px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 14px; height: 14px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                        </svg>
                                    </button>
                                    <button type="button" wire:sort:ignore @click="manageOpen = false" wire:click="confirmDeleteLov({{ $label->id }})" title="Delete" style="display: inline-flex; border: none; background: none; cursor: pointer; color: #dc2626; padding: 2px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 14px; height: 14px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            @empty
                                <div style="text-align: center; padding: 16px 0; color: #9ca3af; font-size: 13px;">
                                    None yet.
                                </div>
                            @endforelse
                        </div>
                    </div>
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

            {{-- New Ticket --}}
            <button
                wire:click="mountAction('createTicket')"
                style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: #2563eb; color: white; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; cursor: pointer;"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Ticket
            </button>
        </div>
    </div>

    {{-- Kanban Board --}}
    <div style="display: flex; gap: 16px; overflow-x: auto; padding-bottom: 16px; min-height: 70vh;">
        @foreach ($this->statuses as $status)
            <div style="flex-shrink: 0; width: 280px; display: flex; flex-direction: column; max-height: calc(100vh - 320px);">
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
                    style="background: #f9fafb; border-radius: 0 0 8px 8px; border: 1px solid #e5e7eb; border-top: none; padding: 8px; min-height: 100px; flex: 1; overflow-y: auto;"
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
