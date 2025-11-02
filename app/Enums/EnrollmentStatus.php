<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case ACTIVE   = 'Ativa';
    case SUSPENDED= 'Suspensa';
    case CANCELED = 'Cancelada';
    case COMPLETED= 'Completa';

    public static function options(): array
    {
        return [
            self::ACTIVE->value    => 'Ativa',
            self::SUSPENDED->value => 'Suspensa',
            self::CANCELED->value  => 'Cancelada',
            self::COMPLETED->value => 'Completa',
        ];
    }

    public static function colors(): array
    {
        return [
            self::ACTIVE->value    => 'success',
            self::SUSPENDED->value => 'warning',
            self::CANCELED->value  => 'danger',
            self::COMPLETED->value => 'info',
        ];
    }
}
