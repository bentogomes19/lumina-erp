<?php

namespace App\Support;

use App\Models\SystemParameter;

/**
 * Lê as regras acadêmicas cadastradas em Parametrizações do Sistema.
 */
class AcademicSettings {

    public const DEFAULT_MINIMUM_ATTENDANCE = 75.0;

    /**
     * Distância, em pontos percentuais, da faixa de atenção abaixo da frequência mínima.
     */
    public const ATTENDANCE_WARNING_MARGIN = 15.0;

    /**
     * Frequência mínima obrigatória, em percentual (0 a 100).
     *
     * @return float
     */
    public static function minimumAttendance(): float {
        $value = SystemParameter::read('academic.minimum_attendance', self::DEFAULT_MINIMUM_ATTENDANCE);

        if (!is_numeric($value)) {
            return self::DEFAULT_MINIMUM_ATTENDANCE;
        }

        return max(0.0, min(100.0, (float) $value));
    }

    /**
     * Cor do indicador de frequência: verde no mínimo, amarelo na faixa de atenção, vermelho abaixo.
     *
     * @param float $rate
     *
     * @return string success|warning|danger
     */
    public static function attendanceColor(float $rate): string {
        $minimum = self::minimumAttendance();

        return match (true) {
            $rate >= $minimum                                   => 'success',
            $rate >= $minimum - self::ATTENDANCE_WARNING_MARGIN => 'warning',
            default                                             => 'danger',
        };
    }

}
