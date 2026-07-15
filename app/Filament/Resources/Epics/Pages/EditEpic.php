<?php

namespace App\Filament\Resources\Epics\Pages;

use App\Filament\Resources\Epics\EpicResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditEpic extends EditRecord
{
    protected static string $resource = EpicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
