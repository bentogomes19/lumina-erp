<?php

namespace App\Enums;

enum ClassStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Aberta',
            self::CLOSED => 'Fechada',
            self::ARCHIVED => 'Arquivada',
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
