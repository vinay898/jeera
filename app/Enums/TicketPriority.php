<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketPriority: string
{
    case Highest = 'highest';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
    case Lowest = 'lowest';

    public function label(): string
    {
        return match ($this) {
            self::Highest => 'Highest',
            self::High => 'High',
            self::Medium => 'Medium',
            self::Low => 'Low',
            self::Lowest => 'Lowest',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Highest => 'heroicon-o-chevron-double-up',
            self::High => 'heroicon-o-chevron-up',
            self::Medium => 'heroicon-o-minus',
            self::Low => 'heroicon-o-chevron-down',
            self::Lowest => 'heroicon-o-chevron-double-down',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Highest => 'danger',
            self::High => 'warning',
            self::Medium => 'info',
            self::Low => 'gray',
            self::Lowest => 'gray',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Highest => 1,
            self::High => 2,
            self::Medium => 3,
            self::Low => 4,
            self::Lowest => 5,
        };
    }
}
