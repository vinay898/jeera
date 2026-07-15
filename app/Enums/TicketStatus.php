<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Testing = 'testing';
    case Done = 'done';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::InReview => 'In Review',
            self::Testing => 'Testing',
            self::Done => 'Done',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'gray',
            self::InProgress => 'info',
            self::InReview => 'warning',
            self::Testing => 'purple',
            self::Done => 'success',
            self::Closed => 'gray',
        };
    }

    public function isResolved(): bool
    {
        return in_array($this, [self::Done, self::Closed], true);
    }
}
