<?php

namespace App\Http\Middleware;
use Illuminate\Http\Request;


class Authenticate
{
    protected function redirectTo(Request $request): ?string
    {
        if (! $request->expectsJson()) {
            // ajuste o nome conforme seu painel (lumina/admin)
            return route('filament.lumina.auth.login');
        }

        return null;
    }
}
