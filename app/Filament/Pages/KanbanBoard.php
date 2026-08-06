<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Tenancy\EmailSettings;
use App\Filament\Resources\Workflows\WorkflowResource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class KanbanBoard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static ?int $navigationSort = -2;

    protected static ?string $navigationLabel = 'Work Board';

    protected static ?string $title = 'Work Board';

    protected static string $routePath = '/';

    protected static ?string $slug = 'kanban';

    public static function getRoutePath(Panel $panel): string
    {
        return static::$routePath;
    }

    protected string $view = 'filament.pages.kanban-board';

    public function getTitle(): string|Htmlable
    {
        $team = Filament::getTenant();

        return $team ? "{$team->name} Work Board" : 'Work Board';
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('workflow')
                    ->label('Workflow')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->url(function () {
                        $workflow = Filament::getTenant()?->workflows()->first();

                        return $workflow
                            ? WorkflowResource::getUrl('edit', ['record' => $workflow])
                            : null;
                    }),
                Action::make('email_settings')
                    ->label('Email Settings')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->url(fn () => EmailSettings::getUrl()),
            ])
                ->label('Settings')
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->button(),
        ];
    }
}
