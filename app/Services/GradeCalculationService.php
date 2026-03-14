<?php

namespace App\Services;

use App\Enums\AssessmentType;
use Illuminate\Support\Collection;

/**
 * Encapsula toda a lógica de cálculo de notas, desacoplada da camada de apresentação.
 *
 * As fórmulas são ponderadas por padrão; o assessment_type = RECOVERY é tratado
 * separadamente e nunca entra na média ponderada principal.
 */
class GradeCalculationService
{
    public const MIN_APPROVAL = 6.0;
    public const MIN_RECOVERY = 4.0;
    public const MAX_SCORE    = 10.0;

    /**
     * Calcula a média ponderada de uma coleção de modelos Grade.
     * Notas com score nulo são excluídas (não tratadas como zero).
     */
    public function weightedAverage(Collection $grades): ?float
    {
        $scored = $grades->filter(fn($g) => $g->score !== null);

        if ($scored->isEmpty()) {
            return null;
        }

        $totalWeight = $scored->sum(fn($g) => $g->weight ?? 1);

        if ($totalWeight <= 0) {
            return null;
        }

        $weightedSum = $scored->sum(fn($g) => $g->score * ($g->weight ?? 1));

        return round($weightedSum / $totalWeight, 2);
    }

    /**
     * Determina o status do aluno com base na média.
     */
    public function status(?float $average, float $minApproval = self::MIN_APPROVAL, float $minRecovery = self::MIN_RECOVERY): string
    {
        if ($average === null)        return 'ongoing';
        if ($average >= $minApproval) return 'approved';
        if ($average >= $minRecovery) return 'recovery';

        return 'failed';
    }

    /**
     * Constrói o relatório completo de notas por disciplina.
     *
     * @param  Collection  $grades      Todos os modelos Grade de uma disciplina (qualquer bimestre).
     * @param  float       $minApproval Média mínima para aprovação direta.
     *
     * @return array{
     *   terms: array<string, array{grades: Collection, average: float|null, recovery: mixed, final_average: float|null, has_grades: bool}>,
     *   overall_average: float|null,
     *   status: string,
     *   points_needed: float|null,
     * }
     */
    public function subjectReport(Collection $grades, float $minApproval = self::MIN_APPROVAL): array
    {
        $regular  = $grades->filter(fn($g) => $g->assessment_type !== AssessmentType::RECOVERY);
        $recovery = $grades->filter(fn($g) => $g->assessment_type === AssessmentType::RECOVERY);

        $termData = [];

        foreach (['b1', 'b2', 'b3', 'b4'] as $termKey) {
            $termGrades = $regular->filter(fn($g) => $this->termValue($g) === $termKey);

            // Vincula a nota de recuperação: prioriza recovery_of_id, senão usa o mesmo bimestre
            $termRecovery = $recovery->first(function ($rg) use ($termKey, $regular) {
                if ($rg->recovery_of_id) {
                    $orig = $regular->firstWhere('id', $rg->recovery_of_id);
                    return $this->termValue($orig) === $termKey;
                }

                return $this->termValue($rg) === $termKey;
            });

            $avg   = $this->weightedAverage($termGrades);
            $final = ($termRecovery !== null && $avg !== null)
                ? max($avg, $termRecovery->score ?? $avg)
                : $avg;

            $termData[$termKey] = [
                'grades'        => $termGrades->sortBy('sequence')->values(),
                'average'       => $avg,
                'recovery'      => $termRecovery,
                'final_average' => $final,
                'has_grades'    => $termGrades->isNotEmpty(),
            ];
        }

        $finals  = collect($termData)->pluck('final_average')->filter(fn($v) => $v !== null);
        $overall = $finals->isNotEmpty() ? round($finals->avg(), 2) : null;
        $status  = $this->status($overall, $minApproval);

        // Estima a pontuação necessária em uma avaliação de peso 1 para atingir a média mínima
        $pointsNeeded = null;
        if ($overall !== null && $overall < $minApproval) {
            $scored       = $regular->filter(fn($g) => $g->score !== null);
            $totalWeight  = $scored->sum(fn($g) => $g->weight ?? 1);
            $weightedSum  = $scored->sum(fn($g) => $g->score * ($g->weight ?? 1));
            $needed       = $minApproval * ($totalWeight + 1) - $weightedSum;
            $pointsNeeded = min(round(max(0, $needed), 2), self::MAX_SCORE);
        }

        return [
            'terms'           => $termData,
            'overall_average' => $overall,
            'status'          => $status,
            'points_needed'   => $pointsNeeded,
        ];
    }

    // ── Auxiliares ───────────────────────────────────────────────────────────

    private function termValue($grade): ?string
    {
        if ($grade === null) return null;

        $term = $grade->term;

        return is_object($term) ? $term->value : $term;
    }
}
