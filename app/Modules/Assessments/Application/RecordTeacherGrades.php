<?php

namespace App\Modules\Assessments\Application;

use App\Models\Assessment;
use App\Models\Enrollment;
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
     * @return array{created:int, updated:int, deleted:int}
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
        $deleted = 0;

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
            &$deleted,
        ): void {
            // Serializa os lançamentos da mesma avaliação antes de procurar notas existentes.
            $lockedAssessment = Assessment::query()->whereKey($assessment->id)->lockForUpdate()->firstOrFail();
            if ((int) $lockedAssessment->teacher_id !== (int) $teacher->id || $lockedAssessment->isClosed()) {
                throw ValidationException::withMessages(['assessment' => 'Avaliação indisponível para este professor.']);
            }

            foreach ($students as $student) {
                $studentId = (int) $student['student_id'];
                $enrollment = Enrollment::query()->find($student['enrollment_id']);
                if (!$enrollment || (int) $enrollment->student_id !== $studentId
                    || (int) $enrollment->class_id !== (int) $lockedAssessment->class_id
                    || (int) $enrollment->school_year_id !== (int) $lockedAssessment->school_year_id) {
                    throw ValidationException::withMessages(["gradeRows.{$studentId}.score" => 'Aluno fora da turma e do ano da avaliação.']);
                }
                $score = $gradeRows[$studentId]['score'] ?? null;
                $comment = $gradeRows[$studentId]['comment'] ?? null;

                if ($score === null || $score === '') {
                    if (!$publish) {
                        $existing = Grade::query()->where('assessment_id', $assessment->id)
                            ->where('student_id', $studentId)->lockForUpdate()->first();
                        if ($existing && !$existing->locked_at) {
                            $existing->delete();
                            $deleted++;
                        }
                        continue;
                    }
                    throw ValidationException::withMessages([
                        "gradeRows.{$studentId}.score" => 'Informe a nota do aluno.',
                    ]);
                }

                if (!is_numeric($score)) {
                    throw ValidationException::withMessages([
                        "gradeRows.{$studentId}.score" => 'Informe uma nota numérica.',
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
                    'assessment_id' => $assessment->id,
                    'student_id'    => $studentId,
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

        return compact('created', 'updated', 'deleted');
    }
}
