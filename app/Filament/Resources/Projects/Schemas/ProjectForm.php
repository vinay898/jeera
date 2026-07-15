<?php

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Project Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, callable $set) {
                                if ($operation === 'create' && $state) {
                                    $set('key', Str::upper(Str::substr(Str::slug($state), 0, 4)));
                                }
                            }),
                        TextInput::make('key')
                            ->required()
                            ->maxLength(10)
                            ->unique(ignoreRecord: true)
                            ->helperText('A short identifier for tickets (e.g., PROJ)'),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Team & Workflow')
                    ->columns(2)
                    ->schema([
                        Select::make('lead_user_id')
                            ->relationship('lead', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Project Lead'),
                        Select::make('default_assignee_id')
                            ->relationship('defaultAssignee', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Default Assignee'),
                        Select::make('workflow_id')
                            ->relationship('workflow', 'name')
                            ->searchable()
                            ->preload(),
                        Toggle::make('is_archived')
                            ->default(false)
                            ->helperText('Archived projects are hidden from lists'),
                    ]),
            ]);
    }
}
