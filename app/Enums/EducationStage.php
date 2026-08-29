<?php

namespace App\Enums;

enum EducationStage: string {

    case INFANTIL = 'infantil';
    case FUND_I   = 'fundamental_i';
    case FUND_II  = 'fundamental_ii';
    case MEDIO    = 'medio';

    /**
     * Retorna o rótulo legível da etapa de ensino.
     *
     * @return string
     */
    public function label(): string {
        return match ($this) {
            self::INFANTIL => 'Educação Infantil',
            self::FUND_I   => 'Ensino Fundamental I',
            self::FUND_II  => 'Ensino Fundamental II',
            self::MEDIO    => 'Ensino Médio',
        };
    }

    /**
     * Retorna as etapas de ensino indexadas pelos respectivos valores.
     *
     * @return array
     */
    public static function toArray(): array {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
