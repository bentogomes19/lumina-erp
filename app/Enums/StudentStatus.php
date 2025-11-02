<?php

namespace App\Enums;
enum StudentStatus:string {
    case ACTIVE='active';
    case INACTIVE='inactive';
    case SUSPENDED='suspended';
    case GRADUATED='graduated';

    public static function options(): array {
        return [
            self::ACTIVE->value=>'Ativo',
            self::INACTIVE->value=>'Inativo',
            self::SUSPENDED->value=>'Suspenso',
            self::GRADUATED->value=>'Graduado',
        ];
    }
}
