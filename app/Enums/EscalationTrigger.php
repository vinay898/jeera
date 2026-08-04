<?php

declare(strict_types=1);

namespace App\Enums;

enum EscalationTrigger: string
{
    case SlaResponseBreach = 'sla_response_breach';
    case SlaResolutionBreach = 'sla_resolution_breach';
    case AwaitingResponse = 'awaiting_response';
    case Stale = 'stale';

    public function label(): string
    {
        return match ($this) {
            self::SlaResponseBreach => 'SLA Response Breach',
            self::SlaResolutionBreach => 'SLA Resolution Breach',
            self::AwaitingResponse => 'Awaiting User Response',
            self::Stale => 'Stale Ticket',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SlaResponseBreach => 'Ticket has not received a response within the SLA response time',
            self::SlaResolutionBreach => 'Ticket has not been resolved within the SLA resolution time',
            self::AwaitingResponse => 'Ticket is waiting for user/customer response',
            self::Stale => 'Ticket has had no activity for a specified period',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SlaResponseBreach => 'danger',
            self::SlaResolutionBreach => 'danger',
            self::AwaitingResponse => 'warning',
            self::Stale => 'gray',
        };
    }
}
