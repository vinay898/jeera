<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Member => 'Member',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Full control including team deletion and ownership transfer',
            self::Admin => 'Can manage members and settings',
            self::Member => 'Can work on tickets and projects',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Owner => 'danger',
            self::Admin => 'warning',
            self::Member => 'info',
        };
    }

    public function canManageMembers(): bool
    {
        return match ($this) {
            self::Owner, self::Admin => true,
            self::Member => false,
        };
    }

    public function canManageTeam(): bool
    {
        return $this === self::Owner;
    }
}
