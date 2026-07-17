<?php

namespace App\Filament\Resources\Lovs\Schemas;

use App\Enums\LovType;
use Filament\Facades\Filament;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class LovForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(2)
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Basic Information')
                            ->columnSpanFull()
                            ->schema([
                                Select::make('type')
                                    ->options(LovType::class)
                                    ->required()
                                    ->columnSpan(1),
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),
                                TextInput::make('value')
                                    ->required()
                                    ->maxLength(255)
                                    ->alphaDash()
                                    ->helperText('Unique identifier (lowercase, no spaces)')
                                    ->columnSpan(1),
                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0)
                                    ->columnSpan(1),
                            ])
                            ->columns(2),
                        Section::make('Metadata')
                            ->collapsed()
                            ->schema([
                                KeyValue::make('metadata')
                                    ->helperText('Additional properties (e.g., is_resolved: true for statuses)')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Appearance')
                            ->schema([
                                TextInput::make('icon')
                                    ->prefix('heroicon-o-')
                                    ->placeholder('tag')
                                    ->dehydrateStateUsing(fn (?string $state) => $state ? 'heroicon-o-'.ltrim($state, 'heroicon-o-') : null)
                                    ->formatStateUsing(fn (?string $state) => $state ? str_replace('heroicon-o-', '', $state) : null)
                                    ->helperText(new HtmlString('Browse icons at <a href="https://heroicons.com" target="_blank" class="text-primary-600 hover:underline">heroicons.com</a>')),
                                Select::make('color')
                                    ->options([
                                        'gray' => 'Gray',
                                        'danger' => 'Red',
                                        'warning' => 'Orange',
                                        'success' => 'Green',
                                        'info' => 'Blue',
                                        'purple' => 'Purple',
                                        'pink' => 'Pink',
                                    ])
                                    ->default('gray'),
                            ]),
                        Section::make('Settings')
                            ->schema([
                                Toggle::make('is_default')
                                    ->label('Default selection')
                                    ->helperText('Use as default in forms'),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Show in dropdowns'),
                            ]),
                        Section::make('Scope')
                            ->schema([
                                Select::make('team_id')
                                    ->label('Team')
                                    ->relationship('team', 'name')
                                    ->placeholder('Global (all teams)')
                                    ->helperText('Leave empty for global LOV')
                                    ->default(fn () => Filament::getTenant()?->id),
                            ]),
                    ]),
            ]);
    }
}
