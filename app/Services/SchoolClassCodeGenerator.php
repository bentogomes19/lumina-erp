<?php

namespace App\Services;

use App\Enums\ClassShift;
use App\Models\GradeLevel;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\SystemParameter;
use App\Support\NumberFormat;

/**
 * Gera o código automático da turma a partir do parâmetro "class.number_format".
 *
 * Marcadores aceitos na máscara:
 *  - {year}     ano letivo da turma (ex.: 2026);
 *  - {grade}    ordem da série com dois dígitos (ex.: 05 para o 5º Ano);
 *  - {shift}    sigla do turno (MAN, TAR ou NOI);
 *  - {sequence} próximo número livre entre as turmas com o mesmo prefixo (01, 02...).
 */
class SchoolClassCodeGenerator {

    public const PARAMETER_KEY = 'class.number_format';

    public const DEFAULT_FORMAT = 'TUR-{year}-{grade}-{sequence}';

    private const MAX_LENGTH = 20;

    /**
     * Retorna o código gerado para a turma ou null quando o parâmetro estiver inativo.
     *
     * @param SchoolClass $schoolClass
     *
     * @return string|null
     */
    public function generate(SchoolClass $schoolClass): ?string {
        $format = SystemParameter::read(self::PARAMETER_KEY);

        if (!is_string($format) || trim($format) === '') {
            return null;
        }

        return NumberFormat::render(
            $format,
            [
                '{year}'  => $this->year($schoolClass),
                '{grade}' => $this->grade($schoolClass),
                '{shift}' => $this->shift($schoolClass),
            ],
            SchoolClass::withTrashed(),
            'code',
            pad: 2,
            maxLength: self::MAX_LENGTH,
        );
    }

    /**
     * Retorna o ano letivo da turma, ou o ano corrente se não houver vínculo.
     *
     * @param SchoolClass $schoolClass
     *
     * @return string
     */
    private function year(SchoolClass $schoolClass): string {
        $year = $schoolClass->school_year_id
            ? SchoolYear::query()->whereKey($schoolClass->school_year_id)->value('year')
            : null;

        return (string) ($year ?: now()->year);
    }

    /**
     * Retorna a ordem da série com dois dígitos.
     *
     * @param SchoolClass $schoolClass
     *
     * @return string
     */
    private function grade(SchoolClass $schoolClass): string {
        $order = $schoolClass->grade_level_id
            ? GradeLevel::query()->whereKey($schoolClass->grade_level_id)->value('display_order')
            : null;

        return str_pad((string) ($order ?? $schoolClass->grade_level_id ?? 0), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Retorna a sigla do turno da turma.
     *
     * @param SchoolClass $schoolClass
     *
     * @return string
     */
    private function shift(SchoolClass $schoolClass): string {
        $shift = $schoolClass->shift instanceof ClassShift
            ? $schoolClass->shift
            : ClassShift::tryFrom((string) $schoolClass->shift);

        return match ($shift) {
            ClassShift::MORNING   => 'MAN',
            ClassShift::AFTERNOON => 'TAR',
            ClassShift::EVENING   => 'NOI',
            default               => 'SEM',
        };
    }

}
