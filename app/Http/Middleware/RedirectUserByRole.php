<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectUserByRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        $path = trim($request->path(), '/'); // ex: lumina/students

        // Se o usuário já está em uma rota do painel, não redirecione
        if (str_starts_with($path, 'lumina/') && $path !== 'lumina') {
            return $next($request);
        }

        // Agora sim, decidimos o dashboard:
        if ($user->hasRole('admin')) {
            return redirect('/lumina/dashboard-admin');
        }

        if ($user->hasRole('teacher')) {
            return redirect('/lumina/dashboard-teacher');
        }

        if ($user->hasRole('student')) {
            return redirect('/lumina/dashboard-student');
        }

        return $next($request);
    }
}
