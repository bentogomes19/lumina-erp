<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\PasswordReset as PasswordResetNotification;
use Filament\Auth\Notifications\ResetPassword as FilamentPasswordResetNotification;
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
    }
}
