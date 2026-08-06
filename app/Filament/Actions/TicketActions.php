<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Filament\Resources\Tickets\Schemas\TicketForm;
use App\Models\Project;
use App\Models\Ticket;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class TicketActions
{
    /**
     * Create an edit ticket action with full form and attachments.
     *
     * @param  Closure(): ?int  $getTicketId  Callback to get the ticket ID being edited
     * @param  Closure(): void  $afterSave  Callback to run after saving
     */
    public static function edit(Closure $getTicketId, ?Closure $afterSave = null): Action
    {
        return Action::make('editTicket')
            ->modalHeading('Edit Ticket')
            ->modalWidth('4xl')
            ->fillForm(function () use ($getTicketId): array {
                $ticket = Ticket::with(['categories', 'ticketLabels'])->find($getTicketId());

                if (! $ticket) {
                    return [];
                }

                return [
                    'title' => $ticket->title,
                    'description' => $ticket->description,
                    'project_id' => $ticket->project_id,
                    'type' => $ticket->type instanceof \BackedEnum ? $ticket->type->value : $ticket->type,
                    'status' => $ticket->status instanceof \BackedEnum ? $ticket->status->value : $ticket->status,
                    'priority' => $ticket->priority instanceof \BackedEnum ? $ticket->priority->value : $ticket->priority,
                    'assignee_id' => $ticket->assignee_id,
                    'reporter_id' => $ticket->reporter_id,
                    'epic_id' => $ticket->epic_id,
                    'sprint_id' => $ticket->sprint_id,
                    'parent_id' => $ticket->parent_id,
                    'story_points' => $ticket->story_points,
                    'original_estimate' => $ticket->original_estimate,
                    'time_spent' => $ticket->time_spent,
                    'time_remaining' => $ticket->time_remaining,
                    'due_date' => $ticket->due_date,
                    'categories' => $ticket->categories->pluck('id')->toArray(),
                    'ticketLabels' => $ticket->ticketLabels->pluck('id')->toArray(),
                ];
            })
            ->schema(TicketForm::getComponents())
            ->modalSubmitActionLabel('Save Changes')
            ->action(function (array $data) use ($getTicketId, $afterSave): void {
                $ticket = Ticket::find($getTicketId());

                if (! $ticket) {
                    Notification::make()
                        ->title('Ticket not found')
                        ->danger()
                        ->send();

                    return;
                }

                $ticket->update([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'project_id' => $data['project_id'],
                    'type' => $data['type'],
                    'status' => $data['status'],
                    'priority' => $data['priority'],
                    'assignee_id' => $data['assignee_id'] ?? null,
                    'reporter_id' => $data['reporter_id'] ?? null,
                    'epic_id' => $data['epic_id'] ?? null,
                    'sprint_id' => $data['sprint_id'] ?? null,
                    'parent_id' => $data['parent_id'] ?? null,
                    'story_points' => $data['story_points'] ?? null,
                    'original_estimate' => $data['original_estimate'] ?? null,
                    'time_spent' => $data['time_spent'] ?? null,
                    'time_remaining' => $data['time_remaining'] ?? null,
                    'due_date' => $data['due_date'] ?? null,
                ]);

                // Sync categories
                if (isset($data['categories'])) {
                    $ticket->categories()->sync($data['categories']);
                }

                // Sync labels
                if (isset($data['ticketLabels'])) {
                    $ticket->ticketLabels()->sync($data['ticketLabels']);
                }

                if ($afterSave) {
                    $afterSave();
                }

                Notification::make()
                    ->title('Ticket updated')
                    ->success()
                    ->send();
            });
    }

    /**
     * Create a new ticket action with full form.
     *
     * @param  ?int  $defaultProjectId  Default project to select
     * @param  Closure(): void  $afterSave  Callback to run after saving
     */
    public static function create(?int $defaultProjectId = null, ?Closure $afterSave = null): Action
    {
        return Action::make('createTicket')
            ->label('Ticket')
            ->icon(Heroicon::OutlinedTicket)
            ->modalHeading('Create Ticket')
            ->modalWidth('4xl')
            ->fillForm(function () use ($defaultProjectId): array {
                return [
                    'project_id' => $defaultProjectId,
                    'reporter_id' => auth()->id(),
                    'type' => 'task',
                    'status' => 'open',
                    'priority' => 'medium',
                ];
            })
            ->schema(TicketForm::getComponents())
            ->modalSubmitActionLabel('Create Ticket')
            ->action(function (array $data) use ($afterSave): void {
                $team = Filament::getTenant();
                $project = isset($data['project_id']) ? Project::find($data['project_id']) : null;

                // Generate ticket key based on project or team
                if ($project) {
                    $ticketCount = Ticket::where('project_id', $project->id)->count() + 1;
                    $key = $project->key.'-'.$ticketCount;
                } else {
                    $ticketCount = Ticket::where('team_id', $team->id)->whereNull('project_id')->count() + 1;
                    $key = strtoupper($team->slug).'-'.$ticketCount;
                }

                $ticket = Ticket::create([
                    'team_id' => $team->id,
                    'project_id' => $data['project_id'] ?? null,
                    'key' => $key,
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'type' => $data['type'],
                    'status' => $data['status'],
                    'priority' => $data['priority'],
                    'assignee_id' => $data['assignee_id'] ?? null,
                    'reporter_id' => $data['reporter_id'] ?? auth()->id(),
                    'epic_id' => $data['epic_id'] ?? null,
                    'sprint_id' => $data['sprint_id'] ?? null,
                    'parent_id' => $data['parent_id'] ?? null,
                    'story_points' => $data['story_points'] ?? null,
                    'original_estimate' => $data['original_estimate'] ?? null,
                    'time_spent' => $data['time_spent'] ?? 0,
                    'time_remaining' => $data['time_remaining'] ?? null,
                    'due_date' => $data['due_date'] ?? null,
                ]);

                // Sync categories
                if (! empty($data['categories'])) {
                    $ticket->categories()->sync($data['categories']);
                }

                // Sync labels
                if (! empty($data['ticketLabels'])) {
                    $ticket->ticketLabels()->sync($data['ticketLabels']);
                }

                if ($afterSave) {
                    $afterSave();
                }

                Notification::make()
                    ->title('Ticket created')
                    ->success()
                    ->send();
            });
    }

    /**
     * Create a table edit action for Filament resource tables.
     *
     * @param  Closure(): void  $afterSave  Callback to run after saving
     */
    public static function tableEdit(?Closure $afterSave = null): Action
    {
        return Action::make('editTicket')
            ->label('Edit')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->modalHeading('Edit Ticket')
            ->modalWidth('4xl')
            ->fillForm(function (Ticket $record): array {
                $ticket = $record->load(['categories', 'ticketLabels']);

                return [
                    'title' => $ticket->title,
                    'description' => $ticket->description,
                    'project_id' => $ticket->project_id,
                    'type' => $ticket->type instanceof \BackedEnum ? $ticket->type->value : $ticket->type,
                    'status' => $ticket->status instanceof \BackedEnum ? $ticket->status->value : $ticket->status,
                    'priority' => $ticket->priority instanceof \BackedEnum ? $ticket->priority->value : $ticket->priority,
                    'assignee_id' => $ticket->assignee_id,
                    'reporter_id' => $ticket->reporter_id,
                    'epic_id' => $ticket->epic_id,
                    'sprint_id' => $ticket->sprint_id,
                    'parent_id' => $ticket->parent_id,
                    'story_points' => $ticket->story_points,
                    'original_estimate' => $ticket->original_estimate,
                    'time_spent' => $ticket->time_spent,
                    'time_remaining' => $ticket->time_remaining,
                    'due_date' => $ticket->due_date,
                    'categories' => $ticket->categories->pluck('id')->toArray(),
                    'ticketLabels' => $ticket->ticketLabels->pluck('id')->toArray(),
                ];
            })
            ->schema(TicketForm::getComponents())
            ->modalSubmitActionLabel('Save Changes')
            ->action(function (array $data, Ticket $record) use ($afterSave): void {
                $record->update([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'project_id' => $data['project_id'],
                    'type' => $data['type'],
                    'status' => $data['status'],
                    'priority' => $data['priority'],
                    'assignee_id' => $data['assignee_id'] ?? null,
                    'reporter_id' => $data['reporter_id'] ?? null,
                    'epic_id' => $data['epic_id'] ?? null,
                    'sprint_id' => $data['sprint_id'] ?? null,
                    'parent_id' => $data['parent_id'] ?? null,
                    'story_points' => $data['story_points'] ?? null,
                    'original_estimate' => $data['original_estimate'] ?? null,
                    'time_spent' => $data['time_spent'] ?? null,
                    'time_remaining' => $data['time_remaining'] ?? null,
                    'due_date' => $data['due_date'] ?? null,
                ]);

                // Sync categories
                if (isset($data['categories'])) {
                    $record->categories()->sync($data['categories']);
                }

                // Sync labels
                if (isset($data['ticketLabels'])) {
                    $record->ticketLabels()->sync($data['ticketLabels']);
                }

                if ($afterSave) {
                    $afterSave();
                }

                Notification::make()
                    ->title('Ticket updated')
                    ->success()
                    ->send();
            });
    }
}
