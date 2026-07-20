<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Actions\TicketActions;
use App\Filament\Resources\Tickets\TicketResource;
use Filament\Resources\Pages\ListRecords;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            TicketActions::create(afterSave: fn () => $this->resetTable()),
        ];
    }
}
