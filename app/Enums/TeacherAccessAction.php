<?php

namespace App\Enums;

enum TeacherAccessAction: string {

    case NONE              = 'none';
    case CREATE            = 'create';
    case CREATE_AND_INVITE = 'create_and_invite';

    /**
     * Retorna as ações disponíveis para a conta de acesso do professor.
     *
     * @return array<string, string>
     */
    public static function options(): array {
        return [
            self::NONE->value              => 'Não criar acesso agora',
            self::CREATE->value            => 'Criar usuário sem enviar convite',
            self::CREATE_AND_INVITE->value => 'Criar usuário e enviar convite',
        ];
    }

    /**
     * Indica se a ação cria um usuário.
     *
     * @return bool
     */
    public function createsUser(): bool {
        return $this !== self::NONE;
    }

    /**
     * Indica se a ação também envia o convite de primeiro acesso.
     *
     * @return bool
     */
    public function sendsInvitation(): bool {
        return $this === self::CREATE_AND_INVITE;
    }
}
