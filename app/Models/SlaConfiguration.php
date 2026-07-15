<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketPriority;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id',
    'project_id',
    'name',
    'priority',
    'response_time_minutes',
    'resolution_time_minutes',
    'business_hours_only',
    'is_active',
])]
class SlaConfiguration extends Model
{
    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'business_hours_only' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
