<?php

namespace App\Filament\Resources\Workflows;

use App\Filament\Pages\KanbanBoard;
use App\Filament\Resources\Workflows\Pages\EditWorkflow;
use App\Filament\Resources\Workflows\Schemas\WorkflowForm;
use App\Models\Workflow;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class WorkflowResource extends Resource
{
    protected static ?string $model = Workflow::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function getIndexUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return KanbanBoard::getUrl(panel: $panel, tenant: $tenant);
    }

    public static function form(Schema $schema): Schema
    {
        return WorkflowForm::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'edit' => EditWorkflow::route('/{record}'),
        ];
    }
}
