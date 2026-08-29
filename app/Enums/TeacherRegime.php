<?php

namespace App\Enums;

enum TeacherRegime: string {

    case CLT       = 'clt';
    case PJ        = 'pj';
    case TEMP      = 'temp';
    case STATUTORY = 'statutory';

    /**
     * Retorna o rótulo legível do regime de trabalho do professor.
     *
     * @return string
     */
    public function label(): string {
        return match($this) {
            self::CLT       => 'CLT',
            self::PJ        => 'PJ',
            self::TEMP      => 'Temporário',
            self::STATUTORY => 'Estatutário',
        };
    }
    /**
     * Retorna os regimes de trabalho disponíveis para seleção.
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
