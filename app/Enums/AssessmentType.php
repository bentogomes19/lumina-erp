<?php

namespace App\Enums;
enum AssessmentType: string
{
    case TEST = 'test';
    case QUIZ = 'quiz';
    case WORK = 'work';
    case PROJECT = 'project';
    case PARTICIPATION = 'participation';
    case RECOVERY = 'recovery';

    public static function options(): array
    {
        return [
            'test' => 'Prova', 'quiz' => 'Quiz', 'work' => 'Trabalho',
            'project' => 'Projeto', 'participation' => 'Participação', 'recovery' => 'Recuperação',
        ];
    }
}
