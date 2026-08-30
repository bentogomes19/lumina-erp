<?php

namespace App\Enums;

enum StudentAccessAction: string {

    case NONE              = 'none';
    case CREATE            = 'create';
    case CREATE_AND_INVITE = 'create_and_invite';
    case INVITE            = 'invite';

    /**
     * Retorna as ações disponíveis quando o aluno ainda não possui usuário.
     *
     * @return array<string, string>
     */
    public static function withoutUserOptions(): array {
        return [
            self::NONE->value              => 'Não criar acesso agora',
            self::CREATE->value            => 'Criar usuário sem enviar convite',
            self::CREATE_AND_INVITE->value => 'Criar usuário e enviar convite',
        ];
    }

    /**
     * Retorna as ações disponíveis quando o aluno já possui usuário.
     *
     * @return array<string, string>
     */
    public static function withUserOptions(): array {
        return [
            self::NONE->value   => 'Manter acesso sem novo convite',
            self::INVITE->value => 'Enviar ou reenviar convite',
        ];
    }

    /**
     * Indica se a ação cria um novo usuário.
     *
     * @return bool
     */
    public function createsUser(): bool {
        return in_array($this, [self::CREATE, self::CREATE_AND_INVITE], true);
    }

    /**
     * Indica se a ação envia um convite de primeiro acesso.
     *
     * @return bool
     */
    public function sendsInvitation(): bool {
        return in_array($this, [self::CREATE_AND_INVITE, self::INVITE], true);
    }
}
