<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EscalationTrigger;
use App\Enums\TicketPriority;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id',
    'project_id',
    'name',
    'trigger_type',
    'trigger_minutes',
    'priority',
    'escalate_to_user_id',
    'escalate_to_role',
    'notify_assignee',
    'notify_reporter',
    'notify_watchers',
    'is_active',
    'sort_order',
])]
class EscalationRule extends Model
{
    protected function casts(): array
    {
        return [
            'trigger_type' => EscalationTrigger::class,
            'priority' => TicketPriority::class,
            'notify_assignee' => 'boolean',
            'notify_reporter' => 'boolean',
            'notify_watchers' => 'boolean',
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function escalateToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalate_to_user_id');
    }

    /**
     * Check if this rule applies to the given ticket.
     */
    public function appliesTo(Ticket $ticket): bool
    {
        // Check project match
        if ($this->project_id && $this->project_id !== $ticket->project_id) {
            return false;
        }

        // Check priority match
        if ($this->priority && $this->priority !== $ticket->priority) {
            return false;
        }

        return true;
    }
}
