<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamRole;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get team members (alias for users).
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->users();
    }

    /**
     * Get team owners.
     *
     * @return BelongsToMany<User, $this>
     */
    public function owners(): BelongsToMany
    {
        return $this->users()->wherePivot('role', TeamRole::Owner->value);
    }

    /**
     * Get team admins.
     *
     * @return BelongsToMany<User, $this>
     */
    public function admins(): BelongsToMany
    {
        return $this->users()->wherePivot('role', TeamRole::Admin->value);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<Workflow, $this>
     */
    public function workflows(): HasMany
    {
        return $this->hasMany(Workflow::class);
    }

    /**
     * @return HasMany<SlaConfiguration, $this>
     */
    public function slaConfigurations(): HasMany
    {
        return $this->hasMany(SlaConfiguration::class);
    }

    /**
     * @return HasMany<CustomField, $this>
     */
    public function customFields(): HasMany
    {
        return $this->hasMany(CustomField::class);
    }

    /**
     * @return HasMany<TeamInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * Get pending invitations.
     *
     * @return HasMany<TeamInvitation, $this>
     */
    public function pendingInvitations(): HasMany
    {
        return $this->invitations()->pending();
    }
}
