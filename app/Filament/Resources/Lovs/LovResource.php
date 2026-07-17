<?php

namespace App\Filament\Resources\Lovs;

use App\Filament\Resources\Lovs\Pages\CreateLov;
use App\Filament\Resources\Lovs\Pages\EditLov;
use App\Filament\Resources\Lovs\Pages\ListLovs;
use App\Filament\Resources\Lovs\Schemas\LovForm;
use App\Filament\Resources\Lovs\Tables\LovsTable;
use App\Models\Lov;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LovResource extends Resource
{
    protected static ?string $model = Lov::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'List of Values';

    protected static ?string $modelLabel = 'LOV';

    protected static ?string $pluralModelLabel = 'LOVs';

    protected static ?int $navigationSort = 100;

    public static function form(Schema $schema): Schema
    {
        return LovForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LovsTable::configure($table);
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
            'index' => ListLovs::route('/'),
            'create' => CreateLov::route('/create'),
            'edit' => EditLov::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $teamId = Filament::getTenant()?->id;

        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->where(function (Builder $query) use ($teamId) {
                $query->whereNull('team_id');
                if ($teamId !== null) {
                    $query->orWhere('team_id', $teamId);
                }
            });
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
