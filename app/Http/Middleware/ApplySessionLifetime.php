<?php

namespace App\Http\Middleware;

use App\Support\SecuritySettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySessionLifetime {

    /**
     * Aplica o parâmetro "security.session_minutes" antes de a sessão ser iniciada.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response {
        config(['session.lifetime' => SecuritySettings::sessionMinutes()]);

        return $next($request);
    }

}
