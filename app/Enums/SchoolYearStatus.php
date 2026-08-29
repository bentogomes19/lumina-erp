<?php

namespace App\Enums;

enum SchoolYearStatus: string {

    case PLANNING = 'planejamento';
    case ACTIVE   = 'ativo';
    case CLOSED   = 'encerrado';

    /**
     * Retorna o rótulo legível do status do ano letivo.
     *
     * @return string
     */
    public function label(): string {
        return match ($this) {
            self::PLANNING => 'Planejamento',
            self::ACTIVE   => 'Ativo',
            self::CLOSED   => 'Encerrado',
        };
    }

    /**
     * Retorna a cor usada para representar o status do ano letivo.
     *
     * @return string
     */
    public function color(): string {
        return match ($this) {
            self::PLANNING => 'warning',
            self::ACTIVE   => 'success',
            self::CLOSED   => 'gray',
        };
    }

    /**
     * Retorna os status de ano letivo indexados pelos respectivos valores.
     *
     * @return array
     */
    public static function toArray(): array {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
