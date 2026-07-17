<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LovType;
use Database\Factories\LovFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lov extends Model
{
    /** @use HasFactory<LovFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'type',
        'name',
        'value',
        'icon',
        'color',
        'sort_order',
        'is_default',
        'is_active',
        'metadata',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => LovType::class,
            'sort_order' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Tickets that have this LOV as a category.
     *
     * @return BelongsToMany<Ticket, $this>
     */
    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'category_ticket')
            ->withTimestamps();
    }

    /**
     * Scope: Global LOVs only (no team).
     *
     * @param  Builder<Lov>  $query
     * @return Builder<Lov>
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('team_id');
    }

    /**
     * Scope: Get LOVs available for a team (global + team-specific).
     *
     * @param  Builder<Lov>  $query
     * @return Builder<Lov>
     */
    public function scopeForTeam(Builder $query, ?int $teamId): Builder
    {
        return $query->where(function (Builder $q) use ($teamId) {
            $q->whereNull('team_id');
            if ($teamId !== null) {
                $q->orWhere('team_id', $teamId);
            }
        });
    }

    /**
     * Scope: Filter by LOV type.
     *
     * @param  Builder<Lov>  $query
     * @return Builder<Lov>
     */
    public function scopeOfType(Builder $query, LovType|string $type): Builder
    {
        $typeValue = $type instanceof LovType ? $type->value : $type;

        return $query->where('type', $typeValue);
    }

    /**
     * Scope: Only active LOVs.
     *
     * @param  Builder<Lov>  $query
     * @return Builder<Lov>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only default LOVs.
     *
     * @param  Builder<Lov>  $query
     * @return Builder<Lov>
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: Order by sort_order.
     *
     * @param  Builder<Lov>  $query
     * @return Builder<Lov>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Check if this LOV represents a resolved status.
     */
    public function isResolved(): bool
    {
        return $this->metadata['is_resolved'] ?? false;
    }

    /**
     * Check if this LOV is global (no team).
     */
    public function isGlobal(): bool
    {
        return $this->team_id === null;
    }

    /**
     * Get the Filament badge color.
     */
    public function getBadgeColor(): string
    {
        return $this->color ?? 'gray';
    }

    /**
     * Get options for Filament Select component.
     *
     * @return array<string, string>
     */
    public static function getOptions(LovType $type, ?int $teamId = null): array
    {
        return static::query()
            ->forTeam($teamId)
            ->ofType($type)
            ->active()
            ->ordered()
            ->pluck('name', 'value')
            ->toArray();
    }

    /**
     * Get LOV records for a type and team.
     *
     * @return Collection<int, Lov>
     */
    public static function getForTeam(LovType $type, ?int $teamId = null): Collection
    {
        return static::query()
            ->forTeam($teamId)
            ->ofType($type)
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * Find a LOV by type and value for a specific team.
     */
    public static function findByValue(LovType $type, string $value, ?int $teamId = null): ?self
    {
        return static::query()
            ->forTeam($teamId)
            ->ofType($type)
            ->where('value', $value)
            ->first();
    }

    /**
     * Get the default LOV for a type and team.
     */
    public static function getDefault(LovType $type, ?int $teamId = null): ?self
    {
        return static::query()
            ->forTeam($teamId)
            ->ofType($type)
            ->default()
            ->first();
    }
}
