<?php

namespace App\Http\Middleware;

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

        /* Se está tentando acessar apenas /lumina (raiz do painel) ou está em uma página que não é um dashboard específico. */
        if ($path === 'lumina' || $path === 'lumina/') {

            /* Redireciona para o dashboard apropriado. */
            if ($user->hasRole('admin')) {
                return redirect('/lumina/dashboard-admin');
            }

            if ($user->hasRole('teacher')) {
                return redirect('/lumina/dashboard-teacher');
            }

            if ($user->hasRole('student')) {
                return redirect('/lumina/dashboard-student');
            }
        }

        /* Se já está em uma rota específica do painel, deixa passar. */
        if (str_starts_with($path, 'lumina/')) {
            return $next($request);
        }

        return $next($request);
    }
}
