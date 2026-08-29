<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\FirstAccessInvitation;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class FirstAccessInvitationService {

    /**
     * Revoga tokens anteriores, cria um convite e o envia ao e-mail do usuário.
     *
     * @param User $user
     *
     * @return string
     */
    public function issue(User $user): string {
        $this->validateUser($user);

        $broker = Password::broker();
        $token  = $broker->createToken($user);
        $url    = Filament::getPanel('lumina')->getResetPasswordUrl($token, $user);

        $user->updateQuietly(['force_password_change' => true]);
        $user->notify(new FirstAccessInvitation($url, $this->expiresInMinutes()));

        return $url;
    }

    /**
     * Revoga o convite ou token de recuperação atualmente emitido para o usuário.
     *
     * @param User $user
     *
     * @return void
     */
    public function revoke(User $user): void {
        Password::broker()->deleteToken($user);
    }

    /**
     * Indica se existe um token emitido e ainda dentro do prazo de validade.
     *
     * @param User $user
     *
     * @return bool
     */
    public function hasActiveToken(User $user): bool {
        $table = config('auth.passwords.users.table', 'password_reset_tokens');

        return DB::table($table)
            ->where('email', $user->getEmailForPasswordReset())
            ->where('created_at', '>=', now()->subMinutes($this->expiresInMinutes()))
            ->exists();
    }

    /**
     * Retorna o prazo de validade configurado para os tokens em minutos.
     *
     * @return int
     */
    public function expiresInMinutes(): int {
        return (int) config('auth.passwords.users.expire', 60);
    }

    /**
     * Garante que a conta pode receber um convite de acesso.
     *
     * @param User $user
     *
     * @return void
     *
     * @throws ValidationException
     */
    private function validateUser(User $user): void {
        if ($user->trashed()) {
            throw ValidationException::withMessages([
                'email' => 'Não é possível enviar convite para um usuário excluído.',
            ]);
        }

        if (!$user->active) {
            throw ValidationException::withMessages([
                'email' => 'Não é possível enviar convite para um usuário inativo.',
            ]);
        }

        if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => 'Informe um e-mail válido antes de enviar o convite.',
            ]);
        }

        $emailInUse = User::withTrashed()
            ->where('email', $user->email)
            ->whereKeyNot($user->getKey())
            ->exists();

        if ($emailInUse) {
            throw ValidationException::withMessages([
                'email' => 'Este e-mail já está vinculado a outro usuário.',
            ]);
        }
    }
}
