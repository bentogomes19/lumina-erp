<?php

namespace App\Enums;

enum LessonStatus: string
{
    case SCHEDULED = 'scheduled';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case RESCHEDULED = 'rescheduled';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Agendada',
            self::COMPLETED => 'Realizada',
            self::CANCELLED => 'Cancelada',
            self::RESCHEDULED => 'Reagendada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SCHEDULED => 'info',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::RESCHEDULED => 'warning',
        };
    }

    public static function options(): array
    {
        return [
            self::SCHEDULED->value => self::SCHEDULED->label(),
            self::COMPLETED->value => self::COMPLETED->label(),
            self::CANCELLED->value => self::CANCELLED->label(),
            self::RESCHEDULED->value => self::RESCHEDULED->label(),
        ];
    }
}
