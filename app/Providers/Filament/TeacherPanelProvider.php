<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Pages\Teacher\DashboardTeacher;
use App\Filament\Pages\Teacher\MyClasses;
use App\Filament\Pages\Teacher\TeacherAssessments;
use App\Filament\Pages\Teacher\TeacherAttendance;
use App\Filament\Pages\Teacher\TeacherGrades;
use App\Filament\Pages\Teacher\TeacherProfile;
use App\Filament\Pages\Teacher\TeacherSchedule;
use App\Filament\Widgets\MyClassesTable;
use App\Filament\Widgets\RecentAttendanceTeacher;
use App\Filament\Widgets\TeacherAttendanceWidget;
use App\Filament\Widgets\TeacherStats;
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
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class TeacherPanelProvider extends PanelProvider {

    public function panel(Panel $panel): Panel {
        return $panel
            ->id('professor')
            ->path('professor')
            ->authGuard('teacher')
            ->brandName(fn (): string => 'Portal do Professor | '.app(SystemBranding::class)->institutionName())
            ->brandLogo(fn (): ?string => app(SystemBranding::class)->logoUrl())
            ->brandLogoHeight('2.25rem')
            ->login(Login::class)
            ->passwordReset(RequestPasswordReset::class)
            ->font('Inter Variable', url: asset('fonts/filament/filament/inter/index.css'), provider: LocalFontProvider::class)
            ->homeUrl(fn (): string => DashboardTeacher::getUrl(panel: 'professor'))
            ->colors([
                'primary' => Color::hex('#32665D'),
            ])
            ->viteTheme('resources/css/filament/professor/theme.css')
            ->pages([
                DashboardTeacher::class,
                MyClasses::class,
                TeacherAssessments::class,
                TeacherAttendance::class,
                TeacherGrades::class,
                TeacherProfile::class,
                TeacherSchedule::class,
            ])
            ->widgets([
                AccountWidget::class,
                MyClassesTable::class,
                RecentAttendanceTeacher::class,
                TeacherAttendanceWidget::class,
                TeacherStats::class,
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
            ]);
    }
}
