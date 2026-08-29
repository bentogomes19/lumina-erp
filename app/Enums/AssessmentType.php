<?php

namespace App\Enums;

enum AssessmentType: string {

    case TEST          = 'test';
    case QUIZ          = 'quiz';
    case WORK          = 'work';
    case PROJECT       = 'project';
    case PARTICIPATION = 'participation';
    case RECOVERY      = 'recovery';

    /**
     * Retorna os tipos de avaliação disponíveis para seleção.
     *
     * @return array
     */
    public static function options(): array {
        return [
            'test'    => 'Prova', 'quiz' => 'Quiz', 'work' => 'Trabalho',
            'project' => 'Projeto', 'participation' => 'Participação', 'recovery' => 'Recuperação',
        ];
    }

    /**
     * Retorna o rótulo legível do tipo de avaliação.
     *
     * @return string
     */
    public function label(): string {
        return match($this) {
            self::TEST          => 'Prova',
            self::QUIZ          => 'Quiz',
            self::WORK          => 'Trabalho',
            self::PROJECT       => 'Projeto',
            self::PARTICIPATION => 'Participação',
            self::RECOVERY      => 'Recuperação',
        };
    }
}
