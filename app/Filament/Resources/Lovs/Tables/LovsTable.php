<?php

namespace App\Filament\Resources\Lovs\Tables;

use App\Enums\LovType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class LovsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->badge()
                    ->icon(fn ($record) => $record->icon)
                    ->color(fn ($record) => $record->color ?? 'gray')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('value')
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->copyable(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('team.name')
                    ->label('Scope')
                    ->default('Global')
                    ->badge()
                    ->color(fn ($record) => $record->team_id ? 'info' : 'success'),
                TextColumn::make('sort_order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_default')
                    ->boolean()
                    ->label('Default')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->toggleable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('type')
                    ->options(LovType::class)
                    ->multiple(),
                TernaryFilter::make('team_id')
                    ->label('Scope')
                    ->placeholder('All')
                    ->trueLabel('Team-specific only')
                    ->falseLabel('Global only')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('team_id'),
                        false: fn ($query) => $query->whereNull('team_id'),
                    ),
                TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order');
    }
}
