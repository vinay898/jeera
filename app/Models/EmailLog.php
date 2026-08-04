<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmailDirection;
use App\Enums\EmailStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id',
    'ticket_id',
    'comment_id',
    'direction',
    'message_id',
    'in_reply_to',
    'references',
    'from_address',
    'to_addresses',
    'cc_addresses',
    'subject',
    'body_text',
    'body_html',
    'status',
    'sent_at',
    'delivered_at',
    'error_message',
])]
class EmailLog extends Model
{
    protected function casts(): array
    {
        return [
            'direction' => EmailDirection::class,
            'status' => EmailStatus::class,
            'references' => 'array',
            'to_addresses' => 'array',
            'cc_addresses' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
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
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<Comment, $this>
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * Mark the email as delivered.
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => EmailStatus::Delivered,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark the email as bounced.
     */
    public function markAsBounced(string $reason): void
    {
        $this->update([
            'status' => EmailStatus::Bounced,
            'error_message' => $reason,
        ]);
    }

    /**
     * Mark the email as failed.
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => EmailStatus::Failed,
            'error_message' => $reason,
        ]);
    }
}
