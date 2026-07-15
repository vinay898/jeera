<?php

declare(strict_types=1);

namespace App\Enums;

enum CustomFieldType: string
{
    case Text = 'text';
    case Number = 'number';
    case Select = 'select';
    case MultiSelect = 'multi_select';
    case Date = 'date';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Number => 'Number',
            self::Select => 'Select',
            self::MultiSelect => 'Multi-Select',
            self::Date => 'Date',
            self::User => 'User',
        };
    }

    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::MultiSelect], true);
    }
}
