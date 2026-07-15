<?php

namespace App\Filament\Resources\Epics;

use App\Filament\Resources\Epics\Pages\CreateEpic;
use App\Filament\Resources\Epics\Pages\EditEpic;
use App\Filament\Resources\Epics\Pages\ListEpics;
use App\Filament\Resources\Epics\Schemas\EpicForm;
use App\Filament\Resources\Epics\Tables\EpicsTable;
use App\Models\Epic;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EpicResource extends Resource
{
    protected static ?string $model = Epic::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static \UnitEnum|string|null $navigationGroup = 'Planning';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return EpicForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EpicsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEpics::route('/'),
            'create' => CreateEpic::route('/create'),
            'edit' => EditEpic::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
