<?php

namespace App\Filament\Pages\Auth;

use Filament\Facades\Filament;

class RequestPasswordReset extends \Filament\Auth\Pages\PasswordReset\RequestPasswordReset {

    /**
     * Prepara o formulário de recuperação e preserva o e-mail do acesso bloqueado.
     *
     * @return void
     */
    public function mount(): void {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill([
            'email' => request()->query('email'),
        ]);
    }
}
