<?php

namespace App\Http\Middleware;

use App\Support\AdministrativeDashboardAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectUserByRole {

    /**
     * Redireciona o usuário autenticado para o painel correspondente ao seu perfil.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        $path = trim($request->path(), '/');

        /* Define uma entrada única para a raiz do painel administrativo. */
        if ($path === 'lumina' || $path === 'lumina/') {
            return redirect(AdministrativeDashboardAccess::destinationFor($user));
        }

        /* Se já está em uma rota específica do painel, deixa passar. */
        if (str_starts_with($path, 'lumina/')) {
            return $next($request);
        }

        return $next($request);
    }
}
