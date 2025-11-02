<?php

namespace App\Enums;

enum SubjectCategory: string
{
    case LINGUAGENS = 'linguagens';
    case MATEMATICA = 'matematica';
    case CIENCIAS_NATUREZA = 'ciencias_da_natureza';
    case CIENCIAS_HUMANAS = 'ciencias_humanas';

    public function label(): string
    {
        return match ($this) {
            self::LINGUAGENS => 'Linguagens',
            self::MATEMATICA => 'Matemática',
            self::CIENCIAS_NATUREZA => 'Ciências da Natureza',
            self::CIENCIAS_HUMANAS => 'Ciências Humanas',
        };
    }

    public static function toArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
