<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordWasChanged {

    /**
     * Impede o acesso ao ERP até que o usuário defina uma nova senha por link seguro.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response {
        $guard = Filament::auth();
        $user  = $guard->user();

        if (!$user?->force_password_change) {
            return $next($request);
        }

        $email = $user->email;

        $guard->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(Filament::getRequestPasswordResetUrl(['email' => $email]))
            ->withErrors(['email' => 'Defina uma nova senha para continuar acessando o ERP.']);
    }
}
