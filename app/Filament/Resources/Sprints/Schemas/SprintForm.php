<?php

namespace App\Filament\Resources\Sprints\Schemas;

use App\Enums\SprintStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SprintForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Sprint Details')
                    ->columns(2)
                    ->schema([
                        Select::make('project_id')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Sprint 1'),
                        Textarea::make('goal')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('What do you want to achieve in this sprint?'),
                    ]),
                Section::make('Timeline')
                    ->columns(3)
                    ->schema([
                        DatePicker::make('start_date')
                            ->default(now()),
                        DatePicker::make('end_date')
                            ->afterOrEqual('start_date')
                            ->default(now()->addWeeks(2)),
                        Select::make('status')
                            ->options(SprintStatus::class)
                            ->default(SprintStatus::Planning)
                            ->required(),
                    ]),
            ]);
    }
}
