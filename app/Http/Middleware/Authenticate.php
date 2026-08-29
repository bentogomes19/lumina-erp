<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;

class Authenticate {

    /**
     * Retorna o endereço para o qual o usuário deve ser redirecionado.
     *
     * @param Request $request
     *
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string {
        if (!$request->expectsJson()) {

            /* ajuste o nome conforme seu painel (lumina/admin) */
            return route('filament.lumina.auth.login');
        }

        return null;
    }
}
