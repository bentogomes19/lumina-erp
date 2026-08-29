<?php

namespace App\Enums;

enum LessonStatus: string {

    case SCHEDULED   = 'scheduled';
    case COMPLETED   = 'completed';
    case CANCELLED   = 'cancelled';
    case RESCHEDULED = 'rescheduled';

    /**
     * Retorna o rótulo legível do status da aula.
     *
     * @return string
     */
    public function label(): string {
        return match ($this) {
            self::SCHEDULED   => 'Agendada',
            self::COMPLETED   => 'Realizada',
            self::CANCELLED   => 'Cancelada',
            self::RESCHEDULED => 'Reagendada',
        };
    }

    /**
     * Retorna a cor usada para representar o status da aula.
     *
     * @return string
     */
    public function color(): string {
        return match ($this) {
            self::SCHEDULED   => 'info',
            self::COMPLETED   => 'success',
            self::CANCELLED   => 'danger',
            self::RESCHEDULED => 'warning',
        };
    }

    /**
     * Retorna os status de aula disponíveis para seleção.
     *
     * @return array
     */
    public static function options(): array {
        return [
            self::SCHEDULED->value   => self::SCHEDULED->label(),
            self::COMPLETED->value   => self::COMPLETED->label(),
            self::CANCELLED->value   => self::CANCELLED->label(),
            self::RESCHEDULED->value => self::RESCHEDULED->label(),
        ];
    }
}
