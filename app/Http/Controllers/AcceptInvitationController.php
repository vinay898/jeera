<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AcceptInvitationController extends Controller
{
    /**
     * Show the invitation acceptance page.
     */
    public function show(string $token): View|RedirectResponse
    {
        $invitation = TeamInvitation::where('token', $token)->first();

        if (! $invitation) {
            return $this->redirectWithError('This invitation link is invalid.');
        }

        if ($invitation->isAccepted()) {
            return $this->redirectWithError('This invitation has already been accepted.');
        }

        if ($invitation->isExpired()) {
            return $this->redirectWithError('This invitation has expired.');
        }

        // If user is logged in and email matches, accept immediately
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            if ($user->email === $invitation->email) {
                return $this->acceptInvitation($invitation, $user);
            }

            // Different email - show a message
            return view('invitations.accept', [
                'invitation' => $invitation,
                'emailMismatch' => true,
                'currentUserEmail' => $user->email,
            ]);
        }

        // Check if user with this email already exists
        $existingUser = User::where('email', $invitation->email)->first();

        if ($existingUser) {
            // Redirect to login with the return URL
            session(['invitation_token' => $token]);

            return redirect()->route('filament.admin.auth.login')
                ->with('status', 'Please log in to accept the invitation.');
        }

        // New user - redirect to register
        session(['invitation_token' => $token]);

        return redirect()->route('filament.admin.auth.register')
            ->with('status', 'Please create an account to join the team.');
    }

    /**
     * Accept the invitation after login/register.
     */
    public function accept(Request $request): RedirectResponse
    {
        $token = session('invitation_token');

        if (! $token) {
            return $this->redirectWithError('No pending invitation found.');
        }

        $invitation = TeamInvitation::where('token', $token)->first();

        if (! $invitation || ! $invitation->isValid()) {
            session()->forget('invitation_token');

            return $this->redirectWithError('This invitation is no longer valid.');
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->email !== $invitation->email) {
            return $this->redirectWithError('This invitation was sent to a different email address.');
        }

        return $this->acceptInvitation($invitation, $user);
    }

    /**
     * Process the invitation acceptance.
     */
    protected function acceptInvitation(TeamInvitation $invitation, User $user): RedirectResponse
    {
        // Check if already a member
        if ($invitation->team->users()->where('users.id', $user->id)->exists()) {
            $invitation->markAsAccepted();
            session()->forget('invitation_token');

            return redirect("/admin/{$invitation->team->slug}")
                ->with('status', 'You are already a member of this team.');
        }

        // Add user to team with the invited role
        $invitation->team->users()->attach($user->id, [
            'role' => $invitation->role->value,
        ]);

        $invitation->markAsAccepted();
        session()->forget('invitation_token');

        return redirect("/admin/{$invitation->team->slug}")
            ->with('status', "You've joined {$invitation->team->name}!");
    }

    /**
     * Redirect with an error message.
     */
    protected function redirectWithError(string $message): RedirectResponse
    {
        return redirect()->route('filament.admin.auth.login')
            ->with('error', $message);
    }
}
