<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'compact_mode',
    'font_size',
    'items_per_page',
    'theme',
    'accent_color',
    'sidebar_collapsed',
    'default_ticket_view',
])]
class UserSetting extends Model
{
    protected function casts(): array
    {
        return [
            'compact_mode' => 'boolean',
            'items_per_page' => 'integer',
            'sidebar_collapsed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
