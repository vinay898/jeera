<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Enums\TicketStatus;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === TicketStatus::Done->value) {
            $parentId = $data['parent_id'] ?? $this->record->parent_id;
            $unresolvedParent = Ticket::findUnresolvedParent($parentId);

            if ($unresolvedParent) {
                Notification::make()
                    ->title("Parent ticket: {$unresolvedParent->title} is not done")
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

        return $data;
    }
}
