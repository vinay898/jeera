<x-filament::page>
    <x-filament::section>
        <x-slot name="heading">
            Team Members
        </x-slot>
        <x-slot name="description">
            Manage who has access to this team
        </x-slot>

        {{ $this->table }}
    </x-filament::section>

    @php
        $pendingInvitations = $this->getTeam()->pendingInvitations()->with('inviter')->get();
    @endphp

    @if($pendingInvitations->count() > 0)
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                Pending Invitations
            </x-slot>
            <x-slot name="description">
                Invitations waiting to be accepted
            </x-slot>

            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($pendingInvitations as $invitation)
                    <div class="flex items-center justify-between py-3">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ $invitation->email }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Invited as {{ $invitation->role->label() }} by {{ $invitation->inviter?->name ?? 'Unknown' }}
                                &middot; Expires {{ $invitation->expires_at->diffForHumans() }}
                            </p>
                        </div>
                        <x-filament::button
                            color="danger"
                            size="sm"
                            wire:click="cancelInvitation({{ $invitation->id }})"
                            wire:confirm="Are you sure you want to cancel this invitation?"
                        >
                            Cancel
                        </x-filament::button>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament::page>
