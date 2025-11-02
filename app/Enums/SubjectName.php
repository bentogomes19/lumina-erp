<?php

namespace App\Enums;

use App\Enums\SubjectCategory;

enum SubjectName: string
{
    // LINGUAGENS
    case PORTUGUES = 'Língua Portuguesa';
    case INGLES = 'Língua Inglesa';
    case ARTE = 'Arte';
    case ED_FISICA = 'Educação Física';

    // MATEMÁTICA
    case MATEMATICA = 'Matemática';

    // CIÊNCIAS DA NATUREZA
    case CIENCIAS = 'Ciências';
    case BIOLOGIA = 'Biologia';
    case FISICA = 'Física';
    case QUIMICA = 'Química';

    // CIÊNCIAS HUMANAS
    case HISTORIA = 'História';
    case GEOGRAFIA = 'Geografia';
    case FILOSOFIA = 'Filosofia';
    case SOCIOLOGIA = 'Sociologia';

    public function category(): SubjectCategory
    {
        return match ($this) {
            self::PORTUGUES, self::INGLES, self::ARTE, self::ED_FISICA => SubjectCategory::LINGUAGENS,
            self::MATEMATICA => SubjectCategory::MATEMATICA,
            self::CIENCIAS, self::BIOLOGIA, self::FISICA, self::QUIMICA => SubjectCategory::CIENCIAS_NATUREZA,
            self::HISTORIA, self::GEOGRAFIA, self::FILOSOFIA, self::SOCIOLOGIA => SubjectCategory::CIENCIAS_HUMANAS,
        };
    }
}
