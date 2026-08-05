<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can see the list of their teams
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Team $team): bool
    {
        return $user->teams()->whereKey($team)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create a team
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Team $team): bool
    {
        $role = $user->teamRole($team);

        return $role === TeamRole::Owner || $role === TeamRole::Admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Team $team): bool
    {
        return $user->isTeamOwner($team);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Team $team): bool
    {
        return $user->isTeamOwner($team);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Team $team): bool
    {
        return $user->isTeamOwner($team);
    }

    /**
     * Determine whether the user can manage team members.
     */
    public function manageMembers(User $user, Team $team): bool
    {
        return $user->canManageTeamMembers($team);
    }

    /**
     * Determine whether the user can invite members.
     */
    public function invite(User $user, Team $team): bool
    {
        return $user->canManageTeamMembers($team);
    }

    /**
     * Determine whether the user can remove members.
     */
    public function removeMember(User $user, Team $team, User $member): bool
    {
        // Can't remove yourself
        if ($user->id === $member->id) {
            return false;
        }

        $userRole = $user->teamRole($team);
        $memberRole = $member->teamRole($team);

        // Owners can remove anyone
        if ($userRole === TeamRole::Owner) {
            return true;
        }

        // Admins can only remove members (not other admins or owners)
        if ($userRole === TeamRole::Admin) {
            return $memberRole === TeamRole::Member;
        }

        return false;
    }
}
