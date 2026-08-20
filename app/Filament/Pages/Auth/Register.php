<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Http\RedirectResponse;
use SensitiveParameter;

class Register extends BaseRegister
{
    public function form(Schema $schema): Schema
    {
        $components = [];

        // Add invite code field if configured AND user is not coming from a team invitation
        if ($this->hasInviteCodeRequirement() && ! $this->hasTeamInvitation()) {
            $components[] = $this->getInviteCodeFormComponent();
        }

        // Add standard registration fields
        $components[] = $this->getNameFormComponent();
        $components[] = $this->getEmailFormComponent();
        $components[] = $this->getPasswordFormComponent();
        $components[] = $this->getPasswordConfirmationFormComponent();

        return $schema->components($components);
    }

    /**
     * Override register to redirect to invitation processing if applicable.
     */
    public function register(): ?RegistrationResponse
    {
        $response = parent::register();

        // If there's a pending team invitation, redirect to process it
        if (session()->has('invitation_token')) {
            return new class implements RegistrationResponse
            {
                public function toResponse($request): RedirectResponse
                {
                    return new RedirectResponse('/invitations/process');
                }
            };
        }

        return $response;
    }

    protected function getInviteCodeFormComponent(): Component
    {
        return TextInput::make('invite_code')
            ->label('Invite Code')
            ->required()
            ->placeholder('Enter your beta invite code')
            ->autofocus()
            ->rules([
                'required',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value !== config('jeera.beta_invite_code')) {
                        $fail('Invalid invite code.');
                    }
                },
            ]);
    }

    /**
     * Check if invite code is required for registration.
     */
    protected function hasInviteCodeRequirement(): bool
    {
        return filled(config('jeera.beta_invite_code'));
    }

    /**
     * Check if user is coming from a team invitation.
     */
    protected function hasTeamInvitation(): bool
    {
        return session()->has('invitation_token');
    }

    /**
     * Remove invite_code from data before creating user.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(#[SensitiveParameter] array $data): array
    {
        unset($data['invite_code']);

        return $data;
    }
}
