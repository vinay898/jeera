<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketType: string
{
    case Bug = 'bug';
    case Story = 'story';
    case Task = 'task';
    case Improvement = 'improvement';
    case Subtask = 'subtask';

    public function label(): string
    {
        return match ($this) {
            self::Bug => 'Bug',
            self::Story => 'Story',
            self::Task => 'Task',
            self::Improvement => 'Improvement',
            self::Subtask => 'Subtask',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Bug => 'heroicon-o-bug-ant',
            self::Story => 'heroicon-o-book-open',
            self::Task => 'heroicon-o-check-circle',
            self::Improvement => 'heroicon-o-arrow-trending-up',
            self::Subtask => 'heroicon-o-list-bullet',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Bug => 'danger',
            self::Story => 'success',
            self::Task => 'info',
            self::Improvement => 'warning',
            self::Subtask => 'gray',
        };
    }
}
