<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LovType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'team_id',
    'project_id',
    'epic_id',
    'parent_id',
    'sprint_id',
    'key',
    'title',
    'description',
    'type',
    'status',
    'priority',
    'assignee_id',
    'reporter_id',
    'story_points',
    'original_estimate',
    'time_spent',
    'time_remaining',
    'due_date',
    'resolution',
    'resolved_at',
    'labels',
    'custom_fields',
    'watchers',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => TicketType::class,
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'labels' => 'array',
            'custom_fields' => 'array',
            'watchers' => 'array',
            'due_date' => 'date',
            'resolved_at' => 'datetime',
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
     * @return BelongsTo<Epic, $this>
     */
    public function epic(): BelongsTo
    {
        return $this->belongsTo(Epic::class);
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsTo<Sprint, $this>
     */
    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * @return HasMany<TimeLog, $this>
     */
    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    /**
     * @return HasMany<TicketHistory, $this>
     */
    public function history(): HasMany
    {
        return $this->hasMany(TicketHistory::class);
    }

    /**
     * Categories assigned to this ticket (from LOV).
     *
     * @return BelongsToMany<Lov, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Lov::class, 'category_ticket')
            ->where('type', LovType::TicketCategory)
            ->withTimestamps();
    }

    /**
     * Labels assigned to this ticket (from LOV).
     *
     * @return BelongsToMany<Lov, $this>
     */
    public function ticketLabels(): BelongsToMany
    {
        return $this->belongsToMany(Lov::class, 'label_ticket')
            ->where('type', LovType::TicketLabel)
            ->withTimestamps();
    }
}
