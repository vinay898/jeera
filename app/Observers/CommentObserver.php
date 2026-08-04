<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Comment;
use App\Models\CommentRead;

class CommentObserver
{
    /**
     * Handle the Comment "created" event.
     * Records first response for SLA tracking and marks as read by author.
     */
    public function created(Comment $comment): void
    {
        $this->recordFirstResponseForSla($comment);
        $this->markAsReadByAuthor($comment);
    }

    /**
     * Record the first response for SLA tracking.
     * Only records if the comment is from the ticket assignee or a team member.
     */
    protected function recordFirstResponseForSla(Comment $comment): void
    {
        $ticket = $comment->ticket;

        if (! $ticket || ! $ticket->slaTracking) {
            return;
        }

        if ($comment->is_internal) {
            return;
        }

        if ($comment->user_id === $ticket->reporter_id) {
            return;
        }

        $ticket->slaTracking->recordFirstResponse();
    }

    /**
     * Mark the comment as read by its author.
     */
    protected function markAsReadByAuthor(Comment $comment): void
    {
        if (! $comment->user_id) {
            return;
        }

        CommentRead::create([
            'comment_id' => $comment->id,
            'user_id' => $comment->user_id,
            'read_at' => now(),
        ]);
    }
}
