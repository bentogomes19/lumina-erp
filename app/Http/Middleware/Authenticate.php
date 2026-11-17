<?php

namespace App\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Http\Exceptions\HttpResponseException;

class Authenticate extends FilamentAuthenticate {

    /**
     * Derruba sessões de usuários inativados ou bloqueados e os envia ao login.
     *
     * A verificação precisa ficar aqui (e não em um middleware separado) porque o
     * Laravel reordena o Authenticate para rodar antes dos demais authMiddleware,
     * e o Filament responderia 403 ao chamar canAccessPanel() para esse usuário.
     *
     * @param \Illuminate\Http\Request $request
     * @param array<string> $guards
     *
     * @return void
     */
    protected function authenticate($request, array $guards): void {
        $guard = Filament::auth();
        $user  = $guard->user();

        if ($user && (!$user->active || $user->is_locked)) {
            $guard->logout();

            // Mantém as autenticações dos outros portais no mesmo navegador.
            $request->session()->regenerate();

            throw new HttpResponseException(
                redirect(Filament::getLoginUrl())
                    ->withErrors(['email' => 'Seu usuário está inativo ou bloqueado. Entre em contato com a administração.'])
            );
        }

        parent::authenticate($request, $guards);
    }

}
