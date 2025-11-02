<?php

namespace App\Enums;

enum EducationStage: string
{
    case INFANTIL = 'infantil';
    case FUND_I = 'fundamental_i';
    case FUND_II = 'fundamental_ii';
    case MEDIO = 'medio';

    public function label(): string
    {
        return match ($this) {
            self::INFANTIL => 'Educação Infantil',
            self::FUND_I => 'Ensino Fundamental I',
            self::FUND_II => 'Ensino Fundamental II',
            self::MEDIO => 'Ensino Médio',
        };
    }

    public static function toArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
