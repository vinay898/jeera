<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Enums\LovType;
use App\Models\Lov;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(2)
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Ticket Details')
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                RichEditor::make('description')
                                    ->columnSpanFull()
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'strike',
                                        'bulletList',
                                        'orderedList',
                                        'link',
                                        'codeBlock',
                                    ]),
                            ]),
                        Section::make('Time Tracking')
                            ->columns(3)
                            ->collapsed()
                            ->schema([
                                TextInput::make('original_estimate')
                                    ->numeric()
                                    ->suffix('minutes')
                                    ->label('Original Estimate'),
                                TextInput::make('time_spent')
                                    ->numeric()
                                    ->suffix('minutes')
                                    ->default(0)
                                    ->label('Time Spent'),
                                TextInput::make('time_remaining')
                                    ->numeric()
                                    ->suffix('minutes')
                                    ->label('Time Remaining'),
                            ]),
                    ]),
                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Classification')
                            ->schema([
                                Select::make('project_id')
                                    ->relationship('project', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live(),
                                Select::make('type')
                                    ->options(fn () => Lov::query()
                                        ->ofType(LovType::TicketType)
                                        ->active()
                                        ->ordered()
                                        ->pluck('name', 'value'))
                                    ->default('task')
                                    ->required()
                                    ->native(false),
                                Select::make('status')
                                    ->options(fn () => Lov::query()
                                        ->ofType(LovType::TicketStatus)
                                        ->active()
                                        ->ordered()
                                        ->pluck('name', 'value'))
                                    ->default('open')
                                    ->required()
                                    ->native(false),
                                Select::make('priority')
                                    ->options(fn () => Lov::query()
                                        ->ofType(LovType::TicketPriority)
                                        ->active()
                                        ->ordered()
                                        ->pluck('name', 'value'))
                                    ->default('medium')
                                    ->required()
                                    ->native(false),
                                Select::make('categories')
                                    ->relationship(
                                        name: 'categories',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query
                                            ->where('type', LovType::TicketCategory)
                                            ->active()
                                            ->ordered()
                                    )
                                    ->multiple()
                                    ->preload()
                                    ->native(false)
                                    ->placeholder('Select categories'),
                                Select::make('ticketLabels')
                                    ->relationship(
                                        name: 'ticketLabels',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query
                                            ->where('type', LovType::TicketLabel)
                                            ->active()
                                            ->ordered()
                                    )
                                    ->multiple()
                                    ->preload()
                                    ->native(false)
                                    ->label('Labels')
                                    ->placeholder('Select labels'),
                            ]),
                        Section::make('People')
                            ->schema([
                                Select::make('assignee_id')
                                    ->relationship('assignee', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->label('Assignee'),
                                Select::make('reporter_id')
                                    ->relationship('reporter', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->default(fn () => auth()->id())
                                    ->label('Reporter'),
                            ]),
                        Section::make('Planning')
                            ->schema([
                                Select::make('epic_id')
                                    ->relationship('epic', 'title')
                                    ->searchable()
                                    ->preload()
                                    ->label('Epic'),
                                Select::make('sprint_id')
                                    ->relationship('sprint', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->label('Sprint'),
                                Select::make('parent_id')
                                    ->relationship('parent', 'title')
                                    ->searchable()
                                    ->preload()
                                    ->label('Parent Ticket'),
                                TextInput::make('story_points')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100),
                                DatePicker::make('due_date'),
                            ]),
                    ]),
            ]);
    }
}
