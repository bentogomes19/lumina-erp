<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case ACTIVE      = 'Ativa';
    case SUSPENDED   = 'Suspensa';
    case LOCKED      = 'Trancada';
    case TRANSFERRED = 'Transferida';
    case CANCELED    = 'Cancelada';
    case COMPLETED   = 'Completa';

    public static function options(): array
    {
        return [
            self::ACTIVE->value      => 'Ativa',
            self::SUSPENDED->value   => 'Suspensa',
            self::LOCKED->value      => 'Trancada',
            self::TRANSFERRED->value => 'Transferida',
            self::CANCELED->value    => 'Cancelada',
            self::COMPLETED->value   => 'Completa',
        ];
    }

    public static function colors(): array
    {
        return [
            self::ACTIVE->value      => 'success',
            self::SUSPENDED->value   => 'warning',
            self::LOCKED->value      => 'gray',
            self::TRANSFERRED->value => 'info',
            self::CANCELED->value    => 'danger',
            self::COMPLETED->value   => 'primary',
        ];
    }
}
