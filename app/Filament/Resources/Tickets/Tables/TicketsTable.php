<?php

namespace App\Filament\Resources\Tickets\Tables;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->title),
                TextColumn::make('type')
                    ->badge()
                    ->icon(fn (TicketType $state): string => $state->icon())
                    ->color(fn (TicketType $state): string => $state->color())
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (TicketStatus $state): string => $state->color())
                    ->sortable(),
                TextColumn::make('priority')
                    ->badge()
                    ->icon(fn (TicketPriority $state): string => $state->icon())
                    ->color(fn (TicketPriority $state): string => $state->color())
                    ->sortable(),
                TextColumn::make('assignee.name')
                    ->label('Assignee')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('sprint.name')
                    ->label('Sprint')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('story_points')
                    ->label('SP')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options(TicketType::class)
                    ->multiple(),
                SelectFilter::make('status')
                    ->options(TicketStatus::class)
                    ->multiple(),
                SelectFilter::make('priority')
                    ->options(TicketPriority::class)
                    ->multiple(),
                SelectFilter::make('project')
                    ->relationship('project', 'name'),
                SelectFilter::make('sprint')
                    ->relationship('sprint', 'name'),
                SelectFilter::make('assignee')
                    ->relationship('assignee', 'name'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
