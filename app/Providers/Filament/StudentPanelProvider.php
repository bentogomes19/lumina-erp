<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\StudentLogin;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Pages\DashboardStudent;
use App\Filament\Widgets\StudentAttendanceStatsWidget;
use App\Filament\Widgets\StudentGradesOverviewWidget;
use App\Filament\Widgets\StudentGradesStatsWidget;
use App\Filament\Widgets\StudentGradesTableWidget;
use App\Filament\Widgets\StudentGradesWidget;
use App\Filament\Widgets\StudentProfileWidget;
use App\Filament\Widgets\UpcomingAssessments;
use App\Support\SystemBranding;
use App\Http\Middleware\EnsurePasswordWasChanged;
use App\Http\Middleware\EnsureUserIsActive;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Khwr\FilamentQt5Theme\FilamentQt5ThemePlugin;

class StudentPanelProvider extends PanelProvider {

    public function panel(Panel $panel): Panel {
        return $panel
            ->id('aluno')
            ->path('aluno')
            ->authGuard('student')
            ->brandName(fn (): string => 'Portal do Aluno | '.app(SystemBranding::class)->institutionName())
            ->brandLogo(fn (): ?string => app(SystemBranding::class)->logoUrl())
            ->brandLogoHeight('2.25rem')
            ->login(StudentLogin::class)
            ->passwordReset(RequestPasswordReset::class)
            ->homeUrl(fn (): string => DashboardStudent::getUrl(panel: 'aluno'))
            ->font('Inter Variable', url: asset('fonts/filament/filament/inter/index.css'), provider: LocalFontProvider::class)
            ->viteTheme('resources/css/filament/aluno/theme.css')
            ->colors([
                'primary' => Color::hex('#3D5A80'),
            ])
            ->pages([
                DashboardStudent::class,
            ])
            ->discoverPages(
                in: app_path('Filament/Pages/Student'),
                for: 'App\\Filament\\Pages\\Student',
            )
            ->widgets([
                StudentAttendanceStatsWidget::class,
                StudentGradesOverviewWidget::class,
                StudentGradesStatsWidget::class,
                StudentGradesTableWidget::class,
                StudentGradesWidget::class,
                StudentProfileWidget::class,
                UpcomingAssessments::class,
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
            ->authMiddleware([
                EnsureUserIsActive::class,
                Authenticate::class,
                EnsurePasswordWasChanged::class,
            ])
            ->plugins([
                FilamentQt5ThemePlugin::make(),
            ]);
    }
}
