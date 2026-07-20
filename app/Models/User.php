<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withTimestamps();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * @return Collection<int, Team>
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->teams;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->teams()->whereKey($tenant)->exists();
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assignee_id');
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function reportedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'reporter_id');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return HasMany<TimeLog, $this>
     */
    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    /**
     * @return HasOne<UserSetting, $this>
     */
    public function setting(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    /**
     * Get a user setting value with optional default.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = $this->setting;

        if (! $setting) {
            return $default;
        }

        return $setting->{$key} ?? $default;
    }

    /**
     * Get all user settings, creating defaults if none exist.
     */
    public function getSettings(): UserSetting
    {
        return $this->setting ?? UserSetting::firstOrCreate(
            ['user_id' => $this->id],
            []
        );
    }
}
