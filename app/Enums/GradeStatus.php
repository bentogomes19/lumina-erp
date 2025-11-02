<?php
namespace App\Enums;
enum GradeStatus: string {
    case POSTED='posted'; case LOCKED='locked';
    public static function options(): array {
        return ['posted'=>'Lançada', 'locked'=>'Fechada'];
    }
}
