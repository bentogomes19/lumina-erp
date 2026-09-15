<?php

use App\Console\Commands\DiagnoseDirectPermissions;
use App\Console\Commands\DiagnoseSchemaIntegrity;
use App\Console\Commands\DiagnoseTeacherDeletion;
use App\Providers\AuthServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        DiagnoseDirectPermissions::class,
        DiagnoseSchemaIntegrity::class,
        DiagnoseTeacherDeletion::class,
    ])
    ->withProviders([
        AuthServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {})
    ->withExceptions(function (Exceptions $exceptions): void {})->create();
