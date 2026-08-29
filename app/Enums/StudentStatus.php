<?php

namespace App\Enums;

enum StudentStatus: string {

    case ACTIVE    = 'active';
    case INACTIVE  = 'inactive';
    case SUSPENDED = 'suspended';
    case GRADUATED = 'graduated';

    /**
     * Retorna os status de aluno disponíveis para seleção.
     *
     * @return array
     */
    public static function options(): array {
        return [
            self::ACTIVE->value    => 'Ativo',
            self::INACTIVE->value  => 'Inativo',
            self::SUSPENDED->value => 'Suspenso',
            self::GRADUATED->value => 'Graduado',
        ];
    }
}
