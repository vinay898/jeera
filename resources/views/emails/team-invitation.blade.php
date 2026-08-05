<x-mail::message>
# You've Been Invited!

**{{ $invitation->inviter?->name ?? 'Someone' }}** has invited you to join **{{ $invitation->team->name }}** as a **{{ $invitation->role->label() }}**.

<x-mail::button :url="$acceptUrl">
Accept Invitation
</x-mail::button>

This invitation will expire on {{ $invitation->expires_at->format('F j, Y \a\t g:i A') }}.

If you did not expect this invitation, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
