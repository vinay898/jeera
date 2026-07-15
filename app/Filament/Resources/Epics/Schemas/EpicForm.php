<?php

namespace App\Filament\Resources\Epics\Schemas;

use App\Enums\EpicStatus;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EpicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Epic Details')
                    ->columns(2)
                    ->schema([
                        Select::make('project_id')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        RichEditor::make('description')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bulletList',
                                'orderedList',
                                'link',
                            ]),
                    ]),
                Section::make('Status & Timeline')
                    ->columns(3)
                    ->schema([
                        Select::make('status')
                            ->options(EpicStatus::class)
                            ->default(EpicStatus::Todo)
                            ->required(),
                        DatePicker::make('start_date'),
                        DatePicker::make('end_date')
                            ->afterOrEqual('start_date'),
                    ]),
                Section::make('Appearance')
                    ->collapsed()
                    ->schema([
                        ColorPicker::make('color')
                            ->helperText('Color used to identify this epic in boards'),
                    ]),
            ]);
    }
}
