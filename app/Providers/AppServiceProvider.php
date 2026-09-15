<?php

namespace App\Providers;

use App\Events\EnrollmentStatusChanged;
use App\Listeners\RecordEnrollmentStatusChange;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\PasswordReset as PasswordResetNotification;
use App\Support\SystemBranding;
use Filament\Auth\Notifications\ResetPassword as FilamentPasswordResetNotification;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {

    /**
     * Registra os serviços gerais da aplicação.
     *
     * @return void
     */
    public function register(): void {
        $this->app->bind(FilamentPasswordResetNotification::class, PasswordResetNotification::class);
    }

    /**
     * Inicializa os serviços gerais da aplicação.
     *
     * @return void
     */
    public function boot(): void {
        view()->share('systemBranding', app(SystemBranding::class));

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_START,
            function (): string {
                $panelId = filament()->getCurrentPanel()?->getId();

                if (!in_array($panelId, ['lumina', 'aluno', 'professor'], true)) {
                    return '';
                }

                return view('filament.partials.panel-theme-storage', [
                    'panelId' => $panelId,
                ])->render();
            },
        );

        app(\Spatie\Permission\PermissionRegistrar::class)
            ->setPermissionClass(Permission::class)
            ->setRoleClass(Role::class);

        Event::listen(Login::class, function (Login $event): void {
            $user = $event->user;

            if (method_exists($user, 'registerSuccessfulLogin')) {
                $user->registerSuccessfulLogin();
            }
        });

        Event::listen(PasswordReset::class, function (PasswordReset $event): void {
            if (!$event->user instanceof User) {
                return;
            }

            $event->user->updateQuietly([
                'force_password_change' => false,
                'login_attempts'        => 0,
            ]);
        });

        Event::listen(EnrollmentStatusChanged::class, RecordEnrollmentStatusChange::class);
    }
}
