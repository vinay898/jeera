<?php

declare(strict_types=1);

namespace App\Filament\Pages\Tenancy;

use App\Enums\TeamRole;
use App\Models\Team;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RegisterTeam extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Create Team';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Team Name')
                    ->required()
                    ->maxLength(255)
                    ->autofocus()
                    ->placeholder('My Awesome Team'),
            ]);
    }

    protected function handleRegistration(array $data): Team
    {
        // Generate a unique slug from the team name
        $baseSlug = Str::slug($data['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (Team::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        $team = Team::create([
            'name' => $data['name'],
            'slug' => $slug,
        ]);

        // Attach the creating user as owner
        $team->users()->attach(auth()->id(), [
            'role' => TeamRole::Owner->value,
        ]);

        return $team;
    }
}
