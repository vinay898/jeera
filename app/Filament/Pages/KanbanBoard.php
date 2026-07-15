<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class KanbanBoard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static \UnitEnum|string|null $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Kanban Board';

    protected static ?string $title = 'Kanban Board';

    protected static ?string $slug = 'kanban';

    protected string $view = 'filament.pages.kanban-board';
}
