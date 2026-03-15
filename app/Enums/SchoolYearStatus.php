<?php

namespace App\Enums;

enum SchoolYearStatus: string
{
    case PLANNING = 'planejamento';
    case ACTIVE   = 'ativo';
    case CLOSED   = 'encerrado';

    public function label(): string
    {
        return match ($this) {
            self::PLANNING => 'Planejamento',
            self::ACTIVE   => 'Ativo',
            self::CLOSED   => 'Encerrado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PLANNING => 'warning',
            self::ACTIVE   => 'success',
            self::CLOSED   => 'gray',
        };
    }

    public static function toArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
