<?php

namespace App\Enums;

enum TeacherStatus: string {
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SABBATICAL = 'sabbatical';
    case TERMINATED = 'terminated';

    public function label(): string {
        return match($this) {
            self::ACTIVE => 'Ativo',
            self::INACTIVE => 'Inativo',
            self::SABBATICAL => 'Afastado',
            self::TERMINATED => 'Desligado',
        };
    }
    public static function options(): array {
        return array_combine(
            array_map(fn($c) => $c->value, self::cases()),
            array_map(fn($c) => $c->label(), self::cases()),
        );
    }
}
