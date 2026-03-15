<?php

namespace App\Enums;

enum TermType: string
{
    case BIMESTER  = 'bimestre';
    case TRIMESTER = 'trimestre';
    case SEMESTER  = 'semestre';
    case ANNUAL    = 'anual';

    public function label(): string
    {
        return match ($this) {
            self::BIMESTER  => 'Bimestre',
            self::TRIMESTER => 'Trimestre',
            self::SEMESTER  => 'Semestre',
            self::ANNUAL    => 'Anual',
        };
    }

    public static function toArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
