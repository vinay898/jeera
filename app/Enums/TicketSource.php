<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketSource: string
{
    case Web = 'web';
    case Email = 'email';
    case Api = 'api';

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Web',
            self::Email => 'Email',
            self::Api => 'API',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Web => 'gray',
            self::Email => 'info',
            self::Api => 'purple',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Web => 'heroicon-o-globe-alt',
            self::Email => 'heroicon-o-envelope',
            self::Api => 'heroicon-o-code-bracket',
        };
    }
}
