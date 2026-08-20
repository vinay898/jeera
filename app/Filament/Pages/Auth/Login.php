<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    /**
     * Override authenticate to redirect to invitation processing if applicable.
     */
    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        // Livewire only picks up redirects issued via $this->redirect() -
        // returning a Responsable object here is inert and does nothing.
        if ($response && session()->has('invitation_token')) {
            $this->redirect('/invitations/process');

            return null;
        }

        return $response;
    }
}
