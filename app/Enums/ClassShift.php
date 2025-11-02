<?php

namespace App\Enums;

enum ClassShift: string
{
    case MORNING = 'morning';
    case AFTERNOON = 'afternoon';
    case EVENING = 'evening';

    public function label(): string
    {
        return match ($this) {
            self::MORNING => 'Manhã',
            self::AFTERNOON => 'Tarde',
            self::EVENING => 'Noite',
        };
    }

    public static function options(): array
    {
        return array_column(self::cases(), 'name') // ignora
            ? array_combine(
                array_map(fn($c) => $c->value, self::cases()),
                array_map(fn($c) => $c->label(), self::cases())
            )
            : [];
    }
}
