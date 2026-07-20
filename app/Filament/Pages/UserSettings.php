<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\UserSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class UserSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Settings';

    protected static ?string $slug = 'settings';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.user-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getRecord()->attributesToArray());
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->action('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make('Visual')
                            ->icon(Heroicon::OutlinedEye)
                            ->schema([
                                Section::make('Display Density')
                                    ->description('Control how compact the interface appears')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('compact_mode')
                                            ->label('Compact Mode')
                                            ->helperText('Reduce padding in forms and tables'),
                                        Select::make('font_size')
                                            ->label('Font Size')
                                            ->options([
                                                'small' => 'Small',
                                                'medium' => 'Medium (Default)',
                                                'large' => 'Large',
                                            ])
                                            ->native(false),
                                    ]),
                                Section::make('Appearance')
                                    ->description('Customize colors and theme')
                                    ->columns(2)
                                    ->schema([
                                        ToggleButtons::make('theme')
                                            ->label('Theme')
                                            ->options([
                                                'light' => 'Light',
                                                'dark' => 'Dark',
                                                'system' => 'System',
                                            ])
                                            ->icons([
                                                'light' => Heroicon::OutlinedSun,
                                                'dark' => Heroicon::OutlinedMoon,
                                                'system' => Heroicon::OutlinedComputerDesktop,
                                            ])
                                            ->inline()
                                            ->columnSpanFull(),
                                        Select::make('accent_color')
                                            ->label('Accent Color')
                                            ->options([
                                                'blue' => 'Blue',
                                                'green' => 'Green',
                                                'purple' => 'Purple',
                                                'amber' => 'Amber',
                                                'rose' => 'Rose',
                                                'cyan' => 'Cyan',
                                            ])
                                            ->native(false),
                                    ]),
                                Section::make('Layout')
                                    ->description('Configure navigation and layout preferences')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('sidebar_collapsed')
                                            ->label('Collapse Sidebar by Default')
                                            ->helperText('Start with sidebar collapsed'),
                                    ]),
                            ]),
                        Tabs\Tab::make('Preferences')
                            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                            ->schema([
                                Section::make('Data Display')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('items_per_page')
                                            ->label('Items Per Page')
                                            ->options([
                                                10 => '10',
                                                25 => '25',
                                                50 => '50',
                                                100 => '100',
                                            ])
                                            ->helperText('Default pagination for tables')
                                            ->native(false),
                                        ToggleButtons::make('default_ticket_view')
                                            ->label('Default Ticket View')
                                            ->options([
                                                'list' => 'List',
                                                'kanban' => 'Kanban',
                                            ])
                                            ->icons([
                                                'list' => Heroicon::OutlinedListBullet,
                                                'kanban' => Heroicon::OutlinedViewColumns,
                                            ])
                                            ->inline(),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $record = $this->getRecord();
        $record->fill($data);
        $record->save();

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->body('Refreshing to apply changes...')
            ->send();

        // Redirect to refresh and apply new styles
        $this->redirect(static::getUrl(), navigate: false);
    }

    public function getRecord(): UserSetting
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->getSettings();
    }
}
