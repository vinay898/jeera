<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\KanbanBoard;
use App\Filament\Pages\Tenancy\RegisterTeam;
use App\Models\Team;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Register dynamic colors based on user's accent_color preference
        // Using a callback so it's evaluated at render time when user is available
        FilamentColor::register(fn () => $this->getUserAccentColors());
    }

    /**
     * Get the accent color palette based on user settings.
     *
     * @return array<string, array<int, string>|string>
     */
    protected function getUserAccentColors(): array
    {
        $user = auth()->user();
        $accentColor = $user?->getSetting('accent_color', 'blue') ?? 'blue';

        $colorMap = [
            'blue' => Color::Blue,
            'green' => Color::Green,
            'purple' => Color::Purple,
            'amber' => Color::Amber,
            'rose' => Color::Rose,
            'cyan' => Color::Cyan,
        ];

        return [
            'primary' => $colorMap[$accentColor] ?? Color::Blue,
        ];
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration(Register::class)
            ->tenant(Team::class, slugAttribute: 'slug')
            ->tenantRegistration(RegisterTeam::class)
            ->homeUrl(fn (): string => KanbanBoard::getUrl(tenant: Filament::getTenant()))
            ->renderHook(
                'panels::head.end',
                fn () => auth()->check() ? Blade::render('<x-settings-styles />') : ''
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn () => Blade::render('<div class="flex items-center me-2"><x-beta-badge /></div>')
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn () => Blade::render('<div class="flex justify-center mb-4"><x-beta-badge /></div>')
            )
            ->renderHook(
                PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE,
                fn () => Blade::render('<div class="flex justify-center mb-4"><x-beta-badge /></div>')
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                // KanbanBoard is discovered via discoverPages() and has routePath '/'
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                // FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
