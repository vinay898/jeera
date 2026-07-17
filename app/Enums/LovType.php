<?php

declare(strict_types=1);

namespace App\Enums;

enum LovType: string
{
    case TicketType = 'ticket_type';
    case TicketStatus = 'ticket_status';
    case TicketPriority = 'ticket_priority';
    case TicketCategory = 'ticket_category';
    case TicketLabel = 'ticket_label';

    public function label(): string
    {
        return match ($this) {
            self::TicketType => 'Ticket Type',
            self::TicketStatus => 'Ticket Status',
            self::TicketPriority => 'Ticket Priority',
            self::TicketCategory => 'Category',
            self::TicketLabel => 'Label',
        };
    }

    public function pluralLabel(): string
    {
        return match ($this) {
            self::TicketType => 'Ticket Types',
            self::TicketStatus => 'Ticket Statuses',
            self::TicketPriority => 'Ticket Priorities',
            self::TicketCategory => 'Categories',
            self::TicketLabel => 'Labels',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TicketType => 'heroicon-o-tag',
            self::TicketStatus => 'heroicon-o-signal',
            self::TicketPriority => 'heroicon-o-flag',
            self::TicketCategory => 'heroicon-o-folder',
            self::TicketLabel => 'heroicon-o-bookmark',
        };
    }
}
