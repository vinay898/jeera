<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketSource;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'ticket_id',
    'user_id',
    'body',
    'is_internal',
    'source',
    'email_message_id',
])]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'source' => TicketSource::class,
        ];
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CommentRead, $this>
     */
    public function reads(): HasMany
    {
        return $this->hasMany(CommentRead::class);
    }

    /**
     * @return HasOne<EmailLog, $this>
     */
    public function emailLog(): HasOne
    {
        return $this->hasOne(EmailLog::class);
    }
}
