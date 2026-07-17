<?php

namespace App\Filament\Resources\Lovs\Pages;

use App\Enums\LovType;
use App\Filament\Resources\Lovs\LovResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLovs extends ListRecords
{
    protected static string $resource = LovResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->icon('heroicon-o-squares-2x2'),
            'ticket_types' => Tab::make('Types')
                ->icon('heroicon-o-tag')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', LovType::TicketType)),
            'ticket_statuses' => Tab::make('Statuses')
                ->icon('heroicon-o-signal')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', LovType::TicketStatus)),
            'ticket_priorities' => Tab::make('Priorities')
                ->icon('heroicon-o-flag')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', LovType::TicketPriority)),
            'categories' => Tab::make('Categories')
                ->icon('heroicon-o-folder')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', LovType::TicketCategory)),
            'labels' => Tab::make('Labels')
                ->icon('heroicon-o-bookmark')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', LovType::TicketLabel)),
        ];
    }
}
