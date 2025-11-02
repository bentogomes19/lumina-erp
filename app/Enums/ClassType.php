<?php

namespace App\Enums;

enum ClassType: string
{
    case REGULAR = 'regular';
    case FULL_TIME = 'full_time';
    case EJA = 'eja';
    case TECHNICAL = 'technical';

    public function label(): string
    {
        return match ($this) {
            self::REGULAR => 'Regular',
            self::FULL_TIME => 'Integral',
            self::EJA => 'EJA',
            self::TECHNICAL => 'Técnico',
        };
    }

    public static function options(): array
    {
        return array_combine(
            array_map(fn($c) => $c->value, self::cases()),
            array_map(fn($c) => $c->label(), self::cases()),
        );
    }
}
