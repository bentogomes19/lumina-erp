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
use App\Http\Middleware\EnsurePasswordWasChanged;
use App\Http\Middleware\EnsureUserIsActive;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Caresome\FilamentAuthDesigner\AuthDesignerPlugin;
use Caresome\FilamentAuthDesigner\Data\AuthPageConfig;
use Caresome\FilamentAuthDesigner\Enums\MediaPosition;
use SpyApp\ThemeEdinburgh\ThemeEdinburghPlugin;

class StudentPanelProvider extends PanelProvider {

    public function panel(Panel $panel): Panel {
        return $panel
            ->id('aluno')
            ->path('aluno')
            ->authGuard('student')
            ->brandName('Portal do Aluno | Lumina')
            ->passwordReset(RequestPasswordReset::class)
            ->homeUrl(fn (): string => DashboardStudent::getUrl(panel: 'aluno'))
            ->viteTheme('resources/css/filament/aluno/theme.css')
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
                ThemeEdinburghPlugin::make(),
                AuthDesignerPlugin::make()
                    ->login(fn (AuthPageConfig $config) => $config
                        ->media(asset('images/student-login-background.png'), alt: 'Pátio de uma escola histórica em Edimburgo')
                        ->mediaPosition(MediaPosition::Cover)
                        ->blur(2)
                        ->usingPage(StudentLogin::class)),
            ]);
    }
}
