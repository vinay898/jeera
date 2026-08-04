<?php

declare(strict_types=1);

namespace App\Enums;

enum EmailDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';

    public function label(): string
    {
        return match ($this) {
            self::Inbound => 'Inbound',
            self::Outbound => 'Outbound',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Inbound => 'info',
            self::Outbound => 'success',
        };
    }
}
