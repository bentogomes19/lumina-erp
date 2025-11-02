<?php

namespace App\Enums;
enum Gender:string {
    case M='M'; case F='F'; case O='O';
    public static function options(): array {
        return ['M'=>'Masculino','F'=>'Feminino','O'=>'Outro'];
    }
}
