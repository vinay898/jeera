<?php

declare(strict_types=1);

namespace App\Enums;

enum EmailStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Bounced = 'bounced';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Queued => 'Queued',
            self::Sent => 'Sent',
            self::Delivered => 'Delivered',
            self::Bounced => 'Bounced',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Queued => 'info',
            self::Sent => 'info',
            self::Delivered => 'success',
            self::Bounced => 'warning',
            self::Failed => 'danger',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Bounced, self::Failed], true);
    }
}
