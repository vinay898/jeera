<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use SensitiveParameter;

class Register extends BaseRegister
{
    public function form(Schema $schema): Schema
    {
        $components = [];

        // Add invite code field if configured
        if ($this->hasInviteCodeRequirement()) {
            $components[] = $this->getInviteCodeFormComponent();
        }

        // Add standard registration fields
        $components[] = $this->getNameFormComponent();
        $components[] = $this->getEmailFormComponent();
        $components[] = $this->getPasswordFormComponent();
        $components[] = $this->getPasswordConfirmationFormComponent();

        return $schema->components($components);
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
