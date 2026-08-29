<?php

namespace App\Enums;

enum Gender: string {

    case M = 'M';
    case F = 'F';
    case O = 'O';

    /**
     * Retorna as opções de gênero disponíveis para seleção.
     *
     * @return array
     */
    public static function options(): array {
        return ['M' => 'Masculino', 'F' => 'Feminino', 'O' => 'Outro'];
    }
}
