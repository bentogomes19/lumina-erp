<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {

    /**
     * Registra os serviços gerais da aplicação.
     *
     * @return void
     */
    public function register(): void {

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
    }
}
