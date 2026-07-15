<?php

namespace App\Filament\Resources\Workflows\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkflowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Workflow Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Toggle::make('is_default')
                            ->default(false)
                            ->helperText('Default workflow for new projects'),
                    ]),
                Section::make('Statuses')
                    ->description('Define the statuses available in this workflow')
                    ->schema([
                        Repeater::make('statuses')
                            ->schema([
                                TextInput::make('name')
                                    ->required(),
                                Select::make('category')
                                    ->options([
                                        'todo' => 'To Do',
                                        'in_progress' => 'In Progress',
                                        'done' => 'Done',
                                    ])
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(3)
                            ->default([
                                ['name' => 'Open', 'category' => 'todo'],
                                ['name' => 'In Progress', 'category' => 'in_progress'],
                                ['name' => 'Done', 'category' => 'done'],
                            ])
                            ->reorderable()
                            ->collapsible(),
                    ]),
                Section::make('Transitions')
                    ->description('Define allowed status transitions')
                    ->collapsed()
                    ->schema([
                        Repeater::make('transitions')
                            ->schema([
                                TextInput::make('from')
                                    ->required()
                                    ->placeholder('From status'),
                                TextInput::make('to')
                                    ->required()
                                    ->placeholder('To status'),
                            ])
                            ->columns(2)
                            ->reorderable()
                            ->collapsible(),
                    ]),
            ]);
    }
}
