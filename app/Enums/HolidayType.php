<?php

namespace App\Enums;

enum HolidayType: string
{
    case NATIONAL_HOLIDAY = 'national_holiday';
    case STATE_HOLIDAY = 'state_holiday';
    case MUNICIPAL_HOLIDAY = 'municipal_holiday';
    case SCHOOL_RECESS = 'school_recess';
    case SCHOOL_EVENT = 'school_event';
    case EXAM_PERIOD = 'exam_period';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::NATIONAL_HOLIDAY => 'Feriado Nacional',
            self::STATE_HOLIDAY => 'Feriado Estadual',
            self::MUNICIPAL_HOLIDAY => 'Feriado Municipal',
            self::SCHOOL_RECESS => 'Recesso Escolar',
            self::SCHOOL_EVENT => 'Evento Escolar',
            self::EXAM_PERIOD => 'Período de Provas',
            self::OTHER => 'Outro',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NATIONAL_HOLIDAY => 'danger',
            self::STATE_HOLIDAY => 'warning',
            self::MUNICIPAL_HOLIDAY => 'info',
            self::SCHOOL_RECESS => 'primary',
            self::SCHOOL_EVENT => 'success',
            self::EXAM_PERIOD => 'secondary',
            self::OTHER => 'gray',
        };
    }

    public static function options(): array
    {
        return [
            self::NATIONAL_HOLIDAY->value => self::NATIONAL_HOLIDAY->label(),
            self::STATE_HOLIDAY->value => self::STATE_HOLIDAY->label(),
            self::MUNICIPAL_HOLIDAY->value => self::MUNICIPAL_HOLIDAY->label(),
            self::SCHOOL_RECESS->value => self::SCHOOL_RECESS->label(),
            self::SCHOOL_EVENT->value => self::SCHOOL_EVENT->label(),
            self::EXAM_PERIOD->value => self::EXAM_PERIOD->label(),
            self::OTHER->value => self::OTHER->label(),
        ];
    }
}
