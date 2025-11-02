<?php

namespace App\Enums;

enum TeacherRegime: string {
    case CLT = 'clt';
    case PJ  = 'pj';
    case TEMP = 'temp';
    case STATUTORY = 'statutory';

    public function label(): string {
        return match($this) {
            self::CLT => 'CLT',
            self::PJ => 'PJ',
            self::TEMP => 'Temporário',
            self::STATUTORY => 'Estatutário',
        };
    }
    public static function options(): array {
        return array_combine(
            array_map(fn($c) => $c->value, self::cases()),
            array_map(fn($c) => $c->label(), self::cases()),
        );
    }
}
