<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jeera — Project Management, Simplified</title>
    <meta name="description" content="The elegant way to manage projects. Track tickets, visualize workflows, and ship on schedule.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-white dark:bg-gray-950 text-gray-900 dark:text-white antialiased" x-data="{ darkMode: window.matchMedia('(prefers-color-scheme: dark)').matches }" :class="{ 'dark': darkMode }">

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-gray-950/80 backdrop-blur-xl border-b border-gray-100 dark:border-gray-800/50">
        <div class="max-w-6xl mx-auto px-6">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center gap-2">
                    <a href="/" class="text-xl font-semibold tracking-tight">
                        jeera
                    </a>
                    <x-beta-badge />
                </div>

                <!-- Right side -->
                <div class="flex items-center gap-4">
                    <!-- Dark mode toggle -->
                    <button
                        @click="darkMode = !darkMode"
                        class="p-2 rounded-full text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors"
                        aria-label="Toggle dark mode"
                    >
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>

                    <!-- CTA Button -->
                    <a
                        href="/admin"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 rounded-full hover:scale-[1.02] active:scale-[0.98] transition-transform"
                    >
                        Get Started
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="min-h-screen flex items-center justify-center px-6 pt-16">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-5xl sm:text-6xl md:text-7xl lg:text-8xl font-semibold tracking-tight leading-[0.9]">
                <span class="block">Work flows.</span>
                <span class="block text-gray-400 dark:text-gray-500">Everything else</span>
                <span class="block text-gray-400 dark:text-gray-500">fades away.</span>
            </h1>

            <p class="mt-8 text-lg sm:text-xl text-gray-500 dark:text-gray-400 max-w-xl mx-auto leading-relaxed">
                The elegant way to manage projects. Track every detail, visualize your workflow, and ship on schedule.
            </p>

            <div class="mt-12">
                <a
                    href="/admin/register"
                    class="inline-flex items-center gap-2 px-8 py-4 text-base font-medium text-white bg-blue-600 rounded-full hover:bg-blue-700 hover:scale-[1.02] active:scale-[0.98] transition-all shadow-lg shadow-blue-600/25"
                >
                    Start for free
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-24 md:py-32 px-6 bg-gray-50 dark:bg-gray-900/50">
        <div class="max-w-5xl mx-auto">
            <div class="grid md:grid-cols-3 gap-6 md:gap-8">
                <!-- Tickets -->
                <div class="group p-8 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 hover:-translate-y-1 hover:shadow-xl hover:shadow-gray-200/50 dark:hover:shadow-gray-900/50 transition-all duration-300">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Tickets</h3>
                    <p class="text-gray-500 dark:text-gray-400 leading-relaxed">
                        Track every detail. From bugs to features, capture it all with rich descriptions, priorities, and custom fields.
                    </p>
                </div>

                <!-- Boards -->
                <div class="group p-8 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 hover:-translate-y-1 hover:shadow-xl hover:shadow-gray-200/50 dark:hover:shadow-gray-900/50 transition-all duration-300">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Boards</h3>
                    <p class="text-gray-500 dark:text-gray-400 leading-relaxed">
                        Visualize your flow. Drag, drop, and organize with intuitive Kanban boards that adapt to your process.
                    </p>
                </div>

                <!-- Sprints -->
                <div class="group p-8 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 hover:-translate-y-1 hover:shadow-xl hover:shadow-gray-200/50 dark:hover:shadow-gray-900/50 transition-all duration-300">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Sprints</h3>
                    <p class="text-gray-500 dark:text-gray-400 leading-relaxed">
                        Ship on schedule. Plan iterations, track velocity, and deliver consistently with time-boxed sprints.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-24 md:py-32 px-6">
        <div class="max-w-3xl mx-auto text-center">
            <p class="text-2xl sm:text-3xl md:text-4xl font-medium text-gray-400 dark:text-gray-500 leading-snug">
                "Built for teams who ship."
            </p>

            <div class="mt-12">
                <a
                    href="/admin"
                    class="inline-flex items-center gap-2 px-8 py-4 text-base font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 rounded-full hover:scale-[1.02] active:scale-[0.98] transition-transform"
                >
                    Get Started
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-8 px-6 border-t border-gray-100 dark:border-gray-800/50">
        <div class="max-w-6xl mx-auto">
            <p class="text-center text-sm text-gray-400 dark:text-gray-500">
                &copy; {{ date('Y') }} Jeera. Crafted with care.
            </p>
        </div>
    </footer>

</body>
</html>
