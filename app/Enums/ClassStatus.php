<?php

namespace App\Enums;

enum ClassStatus: string {

    case OPEN     = 'open';
    case CLOSED   = 'closed';
    case ARCHIVED = 'archived';

    /**
     * Retorna o rótulo legível do status da turma.
     *
     * @return string
     */
    public function label(): string {
        return match ($this) {
            self::OPEN     => 'Aberta',
            self::CLOSED   => 'Fechada',
            self::ARCHIVED => 'Arquivada',
        };
    }

    /**
     * Retorna os status de turma disponíveis para seleção.
     *
     * @return array
     */
    public static function options(): array {
        return array_combine(
            array_map(fn ($c) => $c->value, self::cases()),
            array_map(fn ($c) => $c->label(), self::cases()),
        );
    }
}
