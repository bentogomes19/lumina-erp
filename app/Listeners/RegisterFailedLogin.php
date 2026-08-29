<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegisterFailedLogin {

    /**
     * Registra uma falha de autenticação sem persistir credenciais sensíveis.
     *
     * @param Failed $event
     *
     * @return void
     */
    public function handle(Failed $event): void {
        $user   = $event->user instanceof User ? $event->user : null;
        $reason = $this->reason($user);

        if ($user && $user->active && !$user->is_locked) {
            $user->registerFailedLogin();
        }

        $identity = Str::lower(trim((string)($event->credentials['email'] ?? '')));

        Log::warning('security.authentication.failed', [
            'user_id'       => $user?->getKey(),
            'identity_hash' => hash('sha256', $identity),
            'ip'            => request()->ip(),
            'user_agent'    => request()->userAgent(),
            'reason'        => $reason,
            'blocked'       => $user?->is_locked ?? false,
        ]);
    }

    /**
     * Determina o motivo seguro que será associado ao evento de autenticação.
     *
     * @param User|null $user
     * @return string
     */
    private function reason(?User $user): string {
        if (!$user) {
            return 'identity_not_found';
        }

        if (!$user->active || $user->is_locked) {
            return 'access_denied';
        }

        return 'invalid_credentials';
    }

}
