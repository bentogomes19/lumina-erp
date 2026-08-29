<?php

namespace App\Enums;

enum ClassShift: string {

    case MORNING   = 'morning';
    case AFTERNOON = 'afternoon';
    case EVENING   = 'evening';

    /**
     * Retorna o rótulo legível do turno da turma.
     *
     * @return string
     */
    public function label(): string {
        return match ($this) {
            self::MORNING   => 'Manhã',
            self::AFTERNOON => 'Tarde',
            self::EVENING   => 'Noite',
        };
    }

    /**
     * Retorna os turnos de turma disponíveis para seleção.
     *
     * @return array
     */
    public static function options(): array {
        return array_column(self::cases(), 'name') /* ignora. */
            ? array_combine(
                array_map(fn ($c) => $c->value, self::cases()),
                array_map(fn ($c) => $c->label(), self::cases())
            )
            : [];
    }
}
