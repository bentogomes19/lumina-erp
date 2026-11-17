<?php

namespace App\Support;

use App\Models\SystemParameter;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Throwable;

/**
 * Lê as regras de matrícula cadastradas em Parametrizações do Sistema.
 */
class EnrollmentSettings {

    public const ADULT_AGE = 18;

    /**
     * Indica se alunos menores podem ser matriculados sem responsável legal.
     *
     * Com o parâmetro ausente ou desativado, mantém o comportamento anterior (permite).
     *
     * @return bool
     */
    public static function allowsMinorWithoutGuardian(): bool {
        return (bool) SystemParameter::read('enrollment.allow_without_guardian', true);
    }

    /**
     * Indica se o responsável legal é obrigatório para um aluno com esta data de nascimento.
     *
     * Sem data de nascimento não é possível saber a idade, então não exige.
     *
     * @param CarbonInterface|string|null $birthDate
     *
     * @return bool
     */
    public static function requiresGuardian(CarbonInterface|string|null $birthDate): bool {
        if (blank($birthDate) || self::allowsMinorWithoutGuardian()) {
            return false;
        }

        try {
            $date = $birthDate instanceof CarbonInterface ? $birthDate : Carbon::parse($birthDate);
        } catch (Throwable) {
            return false;
        }

        return $date->age < self::ADULT_AGE;
    }

}
