<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Lov;
use App\Models\Team;

class TeamObserver
{
    /**
     * Handle the Team "created" event.
     * Duplicates all global LOVs (team_id = null) for the new team.
     */
    public function created(Team $team): void
    {
        $globalLovs = Lov::withoutGlobalScopes()
            ->whereNull('team_id')
            ->get();

        foreach ($globalLovs as $lov) {
            Lov::withoutGlobalScopes()->create([
                'team_id' => $team->id,
                'type' => $lov->getRawOriginal('type'),
                'name' => $lov->name,
                'value' => $lov->value,
                'icon' => $lov->icon,
                'color' => $lov->color,
                'sort_order' => $lov->sort_order,
                'is_default' => $lov->is_default,
                'is_active' => $lov->is_active,
                'metadata' => $lov->metadata,
            ]);
        }
    }
}
