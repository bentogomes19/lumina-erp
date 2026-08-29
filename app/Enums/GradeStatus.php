<?php

namespace App\Enums;

enum GradeStatus: string {

    case POSTED = 'posted';
    case LOCKED = 'locked';
    /**
     * Retorna os status de nota disponíveis para seleção.
     *
     * @return array
     */
    public static function options(): array {
        return ['posted' => 'Lançada', 'locked' => 'Fechada'];
    }
}
