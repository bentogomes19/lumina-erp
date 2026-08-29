<?php

namespace App\Enums;

enum AttendanceStatus: string {

    case PRESENT = 'present';
    case ABSENT  = 'absent';
    case LATE    = 'late';
    case EXCUSED = 'excused';

    /**
     * Retorna o rótulo legível do status de frequência.
     *
     * @return string
     */
    public function label(): string {
        return match ($this) {
            self::PRESENT => 'Presente',
            self::ABSENT  => 'Ausente',
            self::LATE    => 'Atrasado',
            self::EXCUSED => 'Falta Justificada',
        };
    }

    /**
     * Retorna a cor usada para representar o status de frequência.
     *
     * @return string
     */
    public function color(): string {
        return match ($this) {
            self::PRESENT => 'success',
            self::ABSENT  => 'danger',
            self::LATE    => 'warning',
            self::EXCUSED => 'info',
        };
    }

    /**
     * Retorna o ícone usado para representar o status de frequência.
     *
     * @return string
     */
    public function icon(): string {
        return match ($this) {
            self::PRESENT => 'fas-circle-check',
            self::ABSENT  => 'fas-circle-xmark',
            self::LATE    => 'fas-clock',
            self::EXCUSED => 'fas-file-lines',
        };
    }

    /**
     * Retorna os status de frequência disponíveis para seleção.
     *
     * @return array
     */
    public static function options(): array {
        return [
            self::PRESENT->value => self::PRESENT->label(),
            self::ABSENT->value  => self::ABSENT->label(),
            self::LATE->value    => self::LATE->label(),
            self::EXCUSED->value => self::EXCUSED->label(),
        ];
    }

    /**
     * Determina se o status deve ser contabilizado como presença.
     *
     * @return bool
     */
    public function countsAsPresent(): bool {
        return in_array($this, [self::PRESENT, self::LATE]);
    }
}
