<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
