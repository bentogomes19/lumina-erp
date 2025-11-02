<?php

namespace App\Enums;

enum AcademicTitle: string {
    case BACHELOR = 'bachelor';
    case LICENTIATE = 'licentiate';
    case SPECIALIST = 'specialist';
    case MASTER = 'master';
    case DOCTOR = 'doctor';

    public function label(): string {
        return match($this) {
            self::BACHELOR => 'Bacharel',
            self::LICENTIATE => 'Licenciado',
            self::SPECIALIST => 'Especialista',
            self::MASTER => 'Mestre',
            self::DOCTOR => 'Doutor',
        };
    }
    public static function options(): array {
        return array_combine(
            array_map(fn($c) => $c->value, self::cases()),
            array_map(fn($c) => $c->label(), self::cases()),
        );
    }
}
