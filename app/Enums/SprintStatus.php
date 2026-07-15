<?php

declare(strict_types=1);

namespace App\Enums;

enum SprintStatus: string
{
    case Planning = 'planning';
    case Active = 'active';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Planning => 'Planning',
            self::Active => 'Active',
            self::Completed => 'Completed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planning => 'gray',
            self::Active => 'info',
            self::Completed => 'success',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Planning => 'heroicon-o-clipboard-document-list',
            self::Active => 'heroicon-o-play',
            self::Completed => 'heroicon-o-check-badge',
        };
    }
}
