<?php

namespace App\Enums;

enum TermType: string {

    case BIMESTER  = 'bimestre';
    case TRIMESTER = 'trimestre';
    case SEMESTER  = 'semestre';
    case ANNUAL    = 'anual';

    /**
     * Retorna o rótulo legível do tipo de período letivo.
     *
     * @return string
     */
    public function label(): string {
        return match ($this) {
            self::BIMESTER  => 'Bimestre',
            self::TRIMESTER => 'Trimestre',
            self::SEMESTER  => 'Semestre',
            self::ANNUAL    => 'Anual',
        };
    }

    /**
     * Retorna os tipos de período letivo indexados pelos respectivos valores.
     *
     * @return array
     */
    public static function toArray(): array {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
