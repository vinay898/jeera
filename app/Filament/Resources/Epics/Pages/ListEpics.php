<?php

namespace App\Filament\Resources\Epics\Pages;

use App\Filament\Resources\Epics\EpicResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEpics extends ListRecords
{
    protected static string $resource = EpicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
