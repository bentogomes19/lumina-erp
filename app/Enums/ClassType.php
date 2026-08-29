<?php

namespace App\Enums;

enum ClassType: string {

    case REGULAR   = 'regular';
    case FULL_TIME = 'full_time';
    case EJA       = 'eja';
    case TECHNICAL = 'technical';

    /**
     * Retorna o rótulo legível do tipo de turma.
     *
     * @return string
     */
    public function label(): string {
        return match ($this) {
            self::REGULAR   => 'Regular',
            self::FULL_TIME => 'Integral',
            self::EJA       => 'EJA',
            self::TECHNICAL => 'Técnico',
        };
    }

    /**
     * Retorna os tipos de turma disponíveis para seleção.
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
