<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Http\RedirectResponse;

class Login extends BaseLogin
{
    /**
     * Override authenticate to redirect to invitation processing if applicable.
     */
    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        if ($response && session()->has('invitation_token')) {
            return new class implements LoginResponse
            {
                public function toResponse($request): RedirectResponse
                {
                    return new RedirectResponse('/invitations/process');
                }
            };
        }

        return $response;
    }
}
