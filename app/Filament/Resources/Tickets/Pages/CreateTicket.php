<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Enums\TicketStatus;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['status'] ?? null) === TicketStatus::Done->value) {
            $unresolvedParent = Ticket::findUnresolvedParent($data['parent_id'] ?? null);

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
