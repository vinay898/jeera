<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Enums\LovType;
use App\Models\Epic;
use App\Models\Lov;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Ticket;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class TicketForm
{
    /**
     * Configure the schema for Filament resource pages.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components(self::getResourceComponents());
    }

    /**
     * Get form components for Filament resource pages (uses relationships).
     *
     * @return array<int, Component>
     */
    public static function getResourceComponents(): array
    {
        return [
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
                                ->fileAttachmentsDisk('public')
                                ->fileAttachmentsDirectory('ticket-descriptions')
                                ->resizableImages()
                                ->toolbarButtons([
                                    'attachFiles',
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
        ];
    }

    /**
     * Get form components for standalone action modals.
     * Switches between classic and modern layout based on config.
     *
     * @return array<int, Component>
     */
    public static function getComponents(): array
    {
        return config('jeera.ticket_form_version') === 'modern'
            ? static::getModernComponents()
            : static::getClassicComponents();
    }

    /**
     * Get classic form components for standalone action modals (uses explicit options).
     * Original multi-grid layout.
     *
     * @return array<int, Component>
     */
    public static function getClassicComponents(): array
    {
        return [
            // Title - full width, prominent
            TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->columnSpanFull()
                ->placeholder('What needs to be done?'),

            // Description - full width, visible height
            RichEditor::make('description')
                ->columnSpanFull()
                ->extraInputAttributes(['style' => 'min-height: 150px'])
                ->fileAttachmentsDisk('public')
                ->fileAttachmentsDirectory('ticket-descriptions')
                ->resizableImages()
                ->toolbarButtons([
                    'attachFiles',
                    'bold',
                    'italic',
                    'bulletList',
                    'orderedList',
                    'link',
                ]),

            // Core fields - 4 columns
            Grid::make(4)
                ->schema([
                    Select::make('project_id')
                        ->label('Project')
                        ->options(fn () => Project::query()
                            ->where('team_id', Filament::getTenant()?->id)
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->preload(),
                    Select::make('type')
                        ->options(fn () => Lov::query()
                            ->ofType(LovType::TicketType)
                            ->active()
                            ->ordered()
                            ->get()
                            ->mapWithKeys(fn ($lov) => [
                                $lov->value => view('components.lov-option', ['lov' => $lov])->render(),
                            ]))
                        ->default('task')
                        ->allowHtml()
                        ->native(false),
                    Select::make('status')
                        ->options(fn () => Lov::query()
                            ->ofType(LovType::TicketStatus)
                            ->active()
                            ->ordered()
                            ->get()
                            ->mapWithKeys(fn ($lov) => [
                                $lov->value => view('components.lov-option', ['lov' => $lov])->render(),
                            ]))
                        ->default('open')
                        ->required()
                        ->allowHtml()
                        ->native(false),
                    Select::make('priority')
                        ->options(fn () => Lov::query()
                            ->ofType(LovType::TicketPriority)
                            ->active()
                            ->ordered()
                            ->get()
                            ->mapWithKeys(fn ($lov) => [
                                $lov->value => view('components.lov-option', ['lov' => $lov])->render(),
                            ]))
                        ->default('medium')
                        ->allowHtml()
                        ->native(false),
                ]),

            // People & Planning - 4 columns
            Grid::make(4)
                ->schema([
                    Select::make('assignee_id')
                        ->label('Assignee')
                        ->options(function () {
                            $team = Filament::getTenant();

                            return $team ? $team->users()->pluck('name', 'users.id') : [];
                        })
                        ->searchable()
                        ->preload(),
                    Select::make('reporter_id')
                        ->label('Reporter')
                        ->options(function () {
                            $team = Filament::getTenant();

                            return $team ? $team->users()->pluck('name', 'users.id') : [];
                        })
                        ->searchable()
                        ->preload()
                        ->default(fn () => auth()->id()),
                    Select::make('epic_id')
                        ->label('Epic')
                        ->options(fn () => Epic::query()
                            ->where('team_id', Filament::getTenant()?->id)
                            ->pluck('title', 'id'))
                        ->searchable()
                        ->preload(),
                    Select::make('sprint_id')
                        ->label('Sprint')
                        ->options(fn () => Sprint::query()
                            ->where('team_id', Filament::getTenant()?->id)
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->preload(),
                ]),

            // Categories & Labels - 2 columns
            Grid::make(2)
                ->schema([
                    Select::make('categories')
                        ->label('Categories')
                        ->options(fn () => Lov::query()
                            ->ofType(LovType::TicketCategory)
                            ->active()
                            ->ordered()
                            ->get()
                            ->mapWithKeys(fn ($lov) => [
                                $lov->id => view('components.lov-option', ['lov' => $lov])->render(),
                            ]))
                        ->multiple()
                        ->preload()
                        ->allowHtml()
                        ->native(false)
                        ->placeholder('Select categories'),
                    Select::make('ticketLabels')
                        ->label('Labels')
                        ->options(fn () => Lov::query()
                            ->ofType(LovType::TicketLabel)
                            ->active()
                            ->ordered()
                            ->get()
                            ->mapWithKeys(fn ($lov) => [
                                $lov->id => view('components.lov-option', ['lov' => $lov])->render(),
                            ]))
                        ->multiple()
                        ->preload()
                        ->allowHtml()
                        ->native(false)
                        ->placeholder('Select labels'),
                ]),

            // Details row - 4 columns
            Grid::make(4)
                ->schema([
                    TextInput::make('story_points')
                        ->label('Story Points')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->placeholder('0'),
                    DatePicker::make('due_date')
                        ->label('Due Date'),
                    Select::make('parent_id')
                        ->label('Parent Ticket')
                        ->options(fn () => Ticket::query()
                            ->where('team_id', Filament::getTenant()?->id)
                            ->pluck('title', 'id'))
                        ->searchable()
                        ->preload(),
                ]),

            // Time Tracking - collapsible fieldset
            Fieldset::make('Time Tracking')
                ->columns(3)
                ->schema([
                    TextInput::make('original_estimate')
                        ->numeric()
                        ->suffix('min')
                        ->label('Estimate')
                        ->placeholder('0'),
                    TextInput::make('time_spent')
                        ->numeric()
                        ->suffix('min')
                        ->default(0)
                        ->label('Spent')
                        ->placeholder('0'),
                    TextInput::make('time_remaining')
                        ->numeric()
                        ->suffix('min')
                        ->label('Remaining')
                        ->placeholder('0'),
                ]),
        ];
    }

    /**
     * Get modern form components with two-column minimal layout.
     * Based on Claude Design mockup.
     *
     * @return array<int, Component>
     */
    public static function getModernComponents(): array
    {
        return [
            Flex::make([
                // Left column - Main content (wider)
                Section::make()
                    ->schema([
                        TextInput::make('title')
                            ->hiddenLabel()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Summarise the work in one line')
                            ->extraInputAttributes([
                                'class' => 'text-lg font-medium border-0 shadow-none focus:ring-0 px-0',
                                'style' => 'font-size: 1.25rem;',
                            ]),
                        Textarea::make('description')
                            ->hiddenLabel()
                            ->rows(8)
                            ->placeholder('Description — context, acceptance criteria, links.')
                            ->extraInputAttributes([
                                'class' => 'border-0 shadow-none focus:ring-0 px-0 resize-none',
                            ]),
                        View::make('components.ticket-form-toolbar'),
                    ])
                    ->extraAttributes(['class' => 'border-0 shadow-none bg-transparent'])
                    ->grow(),

                // Right column - Details sidebar (narrower)
                Section::make('Details')
                    ->grow(false)
                    ->schema([
                        Select::make('type')
                            ->label('Type')
                            ->options(fn () => Lov::query()
                                ->ofType(LovType::TicketType)
                                ->active()
                                ->ordered()
                                ->get()
                                ->mapWithKeys(fn ($lov) => [
                                    $lov->value => view('components.lov-option', ['lov' => $lov])->render(),
                                ]))
                            ->default('task')
                            ->allowHtml()
                            ->native(false),
                        Select::make('priority')
                            ->label('Priority')
                            ->options(fn () => Lov::query()
                                ->ofType(LovType::TicketPriority)
                                ->active()
                                ->ordered()
                                ->get()
                                ->mapWithKeys(fn ($lov) => [
                                    $lov->value => view('components.lov-option', ['lov' => $lov])->render(),
                                ]))
                            ->default('medium')
                            ->allowHtml()
                            ->native(false),
                        Select::make('assignee_id')
                            ->label('Assignee')
                            ->options(function () {
                                $team = Filament::getTenant();

                                return $team ? $team->users()->pluck('name', 'users.id') : [];
                            })
                            ->placeholder('Unassigned')
                            ->searchable()
                            ->preload(),
                        Select::make('sprint_id')
                            ->label('Sprint')
                            ->options(fn () => Sprint::query()
                                ->where('team_id', Filament::getTenant()?->id)
                                ->orderByDesc('id')
                                ->pluck('name', 'id'))
                            ->placeholder('No sprint')
                            ->searchable()
                            ->preload(),
                        DatePicker::make('due_date')
                            ->label('Due')
                            ->placeholder('Set a date'),

                        // More fields - collapsed by default
                        Section::make('More fields')
                            ->collapsed()
                            ->schema([
                                Select::make('project_id')
                                    ->label('Project')
                                    ->options(fn () => Project::query()
                                        ->where('team_id', Filament::getTenant()?->id)
                                        ->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload(),
                                Select::make('status')
                                    ->label('Status')
                                    ->options(fn () => Lov::query()
                                        ->ofType(LovType::TicketStatus)
                                        ->active()
                                        ->ordered()
                                        ->get()
                                        ->mapWithKeys(fn ($lov) => [
                                            $lov->value => view('components.lov-option', ['lov' => $lov])->render(),
                                        ]))
                                    ->default('open')
                                    ->required()
                                    ->allowHtml()
                                    ->native(false),
                                Select::make('epic_id')
                                    ->label('Epic')
                                    ->options(fn () => Epic::query()
                                        ->where('team_id', Filament::getTenant()?->id)
                                        ->pluck('title', 'id'))
                                    ->placeholder('No epic')
                                    ->searchable()
                                    ->preload(),
                                Select::make('parent_id')
                                    ->label('Parent Ticket')
                                    ->options(fn () => Ticket::query()
                                        ->where('team_id', Filament::getTenant()?->id)
                                        ->pluck('title', 'id'))
                                    ->placeholder('None')
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('story_points')
                                    ->label('Story Points')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->placeholder('0'),
                                Select::make('reporter_id')
                                    ->label('Reporter')
                                    ->options(function () {
                                        $team = Filament::getTenant();

                                        return $team ? $team->users()->pluck('name', 'users.id') : [];
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->default(fn () => auth()->id()),
                                Select::make('categories')
                                    ->label('Categories')
                                    ->options(fn () => Lov::query()
                                        ->ofType(LovType::TicketCategory)
                                        ->active()
                                        ->ordered()
                                        ->get()
                                        ->mapWithKeys(fn ($lov) => [
                                            $lov->id => view('components.lov-option', ['lov' => $lov])->render(),
                                        ]))
                                    ->multiple()
                                    ->preload()
                                    ->allowHtml()
                                    ->native(false)
                                    ->placeholder('Select categories'),
                                Select::make('ticketLabels')
                                    ->label('Labels')
                                    ->options(fn () => Lov::query()
                                        ->ofType(LovType::TicketLabel)
                                        ->active()
                                        ->ordered()
                                        ->get()
                                        ->mapWithKeys(fn ($lov) => [
                                            $lov->id => view('components.lov-option', ['lov' => $lov])->render(),
                                        ]))
                                    ->multiple()
                                    ->preload()
                                    ->allowHtml()
                                    ->native(false)
                                    ->placeholder('Select labels'),
                                Fieldset::make('Time Tracking')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('original_estimate')
                                            ->numeric()
                                            ->suffix('min')
                                            ->label('Estimate')
                                            ->placeholder('0'),
                                        TextInput::make('time_spent')
                                            ->numeric()
                                            ->suffix('min')
                                            ->default(0)
                                            ->label('Spent')
                                            ->placeholder('0'),
                                        TextInput::make('time_remaining')
                                            ->numeric()
                                            ->suffix('min')
                                            ->label('Remaining')
                                            ->placeholder('0'),
                                    ]),
                            ]),

                        View::make('components.ticket-form-status-info'),
                    ])
                    ->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-900']),
            ])->from('md'),
        ];
    }
}
