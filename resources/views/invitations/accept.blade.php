<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accept Invitation - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                Team Invitation
            </h1>

            @if($emailMismatch ?? false)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
                    <p class="text-yellow-800 dark:text-yellow-200">
                        This invitation was sent to <strong>{{ $invitation->email }}</strong>,
                        but you're logged in as <strong>{{ $currentUserEmail }}</strong>.
                    </p>
                </div>

                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    To accept this invitation, please log out and sign in with the invited email address.
                </p>

                <div class="flex space-x-4">
                    <a href="{{ route('filament.admin.auth.logout') }}"
                       class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg text-center"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        Log Out
                    </a>
                    <a href="{{ route('filament.admin.pages.dashboard') }}"
                       class="flex-1 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium py-2 px-4 rounded-lg text-center">
                        Go to Dashboard
                    </a>
                </div>

                <form id="logout-form" action="{{ route('filament.admin.auth.logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
            @else
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    You've been invited to join <strong>{{ $invitation->team->name }}</strong>
                    as a <strong>{{ $invitation->role->label() }}</strong>.
                </p>

                <a href="{{ route('invitations.accept.process') }}"
                   class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg text-center">
                    Accept Invitation
                </a>
            @endif
        </div>
    </div>
</body>
</html>
