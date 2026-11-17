<?php

namespace App\Providers;

use App\Events\EnrollmentStatusChanged;
use App\Listeners\RecordEnrollmentStatusChange;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\PasswordReset as PasswordResetNotification;
use App\Support\SecuritySettings;
use App\Support\SystemBranding;
use Filament\Auth\Notifications\ResetPassword as FilamentPasswordResetNotification;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password as PasswordRule;

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

        /* Regra padrão das telas de definição de senha (parâmetro "security.password_min_length"). */
        PasswordRule::defaults(fn (): PasswordRule => PasswordRule::min(SecuritySettings::passwordMinLength()));

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

        /* Aplica a cor institucional às telas próprias do painel administrativo e do portal do aluno. */
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            function (): string {
                $panelId  = filament()->getCurrentPanel()?->getId();
                $branding = app(SystemBranding::class);

                if (!in_array($panelId, ['lumina', 'aluno'], true) || !$branding->hasCustomPrimaryColor()) {
                    return '';
                }

                $color = $branding->primaryColor();

                return '<style>'
                    . ":root{--lumina-primary:{$color};"
                    . "--lumina-primary-strong:color-mix(in srgb,{$color} 78%,black);"
                    . "--lumina-primary-soft:color-mix(in srgb,{$color} 14%,transparent);}"
                    . ":root.dark{--lumina-primary:color-mix(in srgb,{$color} 70%,white);"
                    . "--lumina-primary-strong:color-mix(in srgb,{$color} 85%,white);"
                    . "--lumina-primary-soft:color-mix(in srgb,{$color} 20%,transparent);}"
                    . '</style>';
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
                'password_changed_at'   => now(),
            ]);
        });

        Event::listen(EnrollmentStatusChanged::class, RecordEnrollmentStatusChange::class);
    }
}
