<?php

namespace App\Enums;

/**
 * Motivos válidos para trancamento de matrícula.
 * Lista configurável conforme spec — pode ser expandida sem migration.
 */
enum EnrollmentLockReason: string
{
    case HEALTH    = 'saude';
    case WORK      = 'trabalho';
    case FINANCIAL = 'financeiro';
    case OTHER     = 'outros';

    public function label(): string
    {
        return match ($this) {
            self::HEALTH    => 'Saúde',
            self::WORK      => 'Trabalho',
            self::FINANCIAL => 'Financeiro',
            self::OTHER     => 'Outros',
        };
    }

    public static function options(): array
    {
        return array_combine(
            array_map(fn ($c) => $c->value, self::cases()),
            array_map(fn ($c) => $c->label(), self::cases()),
        );
    }
}
