<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Http\Middleware\EnsurePasswordWasChanged;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RedirectUserByRole;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Khwr\FilamentQt5Theme\FilamentQt5ThemePlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider {

    /**
     * Configura o painel administrativo do Lumina com autenticação, tema, ativos e middleware.
     *
     * @param Panel $panel
     *
     * @return Panel
     *
     * @throws \Exception
     */
    public function panel(Panel $panel): Panel {
        return $panel
            ->default()
            ->id('lumina')
            ->path('lumina')
            ->brandName('Portal Lumina')
            ->login(Login::class)
            ->passwordReset(RequestPasswordReset::class)
            ->font('Inter Variable', url: asset('fonts/filament/filament/inter/index.css'), provider: LocalFontProvider::class, )
            ->viteTheme('resources/css/filament/lumina/theme.css')
            ->assets([
                Js::make(
                    'student-animations',
                    resource_path('js/filament/student-animations.js'),
                ),
            ])
            ->colors([
                'primary' => Color::hex('#3D5A80'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentQt5ThemePlugin::make(),
            ])
            ->authMiddleware([
                EnsureUserIsActive::class,
                Authenticate::class,
                EnsurePasswordWasChanged::class,
                RedirectUserByRole::class,
            ]);
    }
}
