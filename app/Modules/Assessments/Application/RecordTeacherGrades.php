<?php

namespace App\Modules\Assessments\Application;

use App\Models\Assessment;
use App\Models\Grade;
use App\Models\Teacher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Persiste notas lançadas pelo professor fora da camada Filament.
 */
final class RecordTeacherGrades {

    /**
     * @param Teacher $teacher
     * @param Assessment $assessment
     * @param Collection<int, array{student_id:int, enrollment_id:int}> $students
     * @param array<int, array{score:float|int|null, comment:string|null}> $gradeRows
     * @param float $maxScore
     * @param string $term
     * @param string $assessmentType
     * @param int $sequence
     * @param bool $publish
     * @param int|null $postedBy
     *
     * @return array{created:int, updated:int}
     */
    public function execute(
        Teacher $teacher,
        Assessment $assessment,
        Collection $students,
        array $gradeRows,
        float $maxScore,
        string $term,
        string $assessmentType,
        int $sequence,
        bool $publish,
        ?int $postedBy,
    ): array {
        $created = 0;
        $updated = 0;

        DB::transaction(function () use (
            $students,
            $assessment,
            $gradeRows,
            $maxScore,
            $term,
            $assessmentType,
            $sequence,
            $publish,
            $teacher,
            $postedBy,
            &$created,
            &$updated,
        ): void {
            foreach ($students as $student) {
                $studentId = (int) $student['student_id'];
                $score = $gradeRows[$studentId]['score'] ?? null;
                $comment = $gradeRows[$studentId]['comment'] ?? null;

                if ($score === null || $score === '') {
                    throw ValidationException::withMessages([
                        "gradeRows.{$studentId}.score" => 'Informe a nota do aluno.',
                    ]);
                }

                $scoreValue = (float) $score;

                if ($scoreValue < 0) {
                    throw ValidationException::withMessages([
                        "gradeRows.{$studentId}.score" => 'A nota não pode ser menor que zero.',
                    ]);
                }

                if ($scoreValue > $maxScore) {
                    throw ValidationException::withMessages([
                        "gradeRows.{$studentId}.score" => 'A nota não pode ser maior que a nota máxima da avaliação.',
                    ]);
                }

                $attributes = [
                    'enrollment_id'   => $student['enrollment_id'],
                    'subject_id'      => $assessment->subject_id,
                    'term'            => $term,
                    'assessment_type' => $assessmentType,
                    'sequence'        => $sequence,
                ];

                $data = [
                    'assessment_id'   => $assessment->id,
                    'enrollment_id'   => $student['enrollment_id'],
                    'class_id'        => $assessment->class_id,
                    'subject_id'      => $assessment->subject_id,
                    'teacher_id'      => $teacher->id,
                    'term'            => $term,
                    'assessment_type' => $assessmentType,
                    'sequence'        => $sequence,
                    'student_id'      => $studentId,
                    'score'           => $scoreValue,
                    'max_score'       => $maxScore,
                    'weight'          => (float) ($assessment->weight ?? 1),
                    'comment'         => $comment,
                    'date_recorded'   => now()->toDateString(),
                    'posted_by'       => $publish ? $postedBy : null,
                    'locked_at'       => $publish ? now() : null,
                    'origin'          => 'manual',
                ];

                $existing = Grade::query()->where($attributes)->lockForUpdate()->first();

                if ($existing) {
                    if ($existing->locked_at) {
                        throw ValidationException::withMessages([
                            'assessment' => 'Notas já publicadas não podem ser alteradas.',
                        ]);
                    }

                    $existing->update($data);
                    $updated++;
                } else {
                    Grade::create($attributes + $data);
                    $created++;
                }
            }
        });

        return compact('created', 'updated');
    }
}
