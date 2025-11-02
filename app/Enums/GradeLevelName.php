<?php

namespace App\Enums;

enum GradeLevelName: string
{
    // Educação Infantil
    case INFANTIL_1 = 'Pré I';
    case INFANTIL_2 = 'Pré II';

    // Fundamental I
    case FUND_I_1 = '1º Ano';
    case FUND_I_2 = '2º Ano';
    case FUND_I_3 = '3º Ano';
    case FUND_I_4 = '4º Ano';
    case FUND_I_5 = '5º Ano';

    // Fundamental II
    case FUND_II_6 = '6º Ano';
    case FUND_II_7 = '7º Ano';
    case FUND_II_8 = '8º Ano';
    case FUND_II_9 = '9º Ano';

    // Ensino Médio
    case MEDIO_1 = '1ª Série';
    case MEDIO_2 = '2ª Série';
    case MEDIO_3 = '3ª Série';

    public function stage(): string
    {
        return match ($this) {
            self::INFANTIL_1, self::INFANTIL_2 => EducationStage::INFANTIL->value,
            self::FUND_I_1, self::FUND_I_2, self::FUND_I_3, self::FUND_I_4, self::FUND_I_5 => EducationStage::FUND_I->value,
            self::FUND_II_6, self::FUND_II_7, self::FUND_II_8, self::FUND_II_9 => EducationStage::FUND_II->value,
            self::MEDIO_1, self::MEDIO_2, self::MEDIO_3 => EducationStage::MEDIO->value,
        };
    }
}
