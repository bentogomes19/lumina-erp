<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive {

    /**
     * Derruba sessões já autenticadas quando o usuário for inativado ou bloqueado.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response {
        $guard = Filament::auth();
        $user  = $guard->user();

        if ($user && (!$user->active || $user->is_locked)) {
            $guard->logout();

            // Mantém as autenticações dos outros portais no mesmo navegador.
            $request->session()->regenerate();

            return redirect(Filament::getLoginUrl())
                ->withErrors(['email' => 'Seu usuário está inativo ou bloqueado. Entre em contato com a administração.']);
        }

        return $next($request);
    }

}
