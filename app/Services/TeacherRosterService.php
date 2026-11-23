<?php

namespace App\Services;

use App\Enums\AssessmentType;
use App\Enums\EnrollmentStatus;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

/** Mantém registros históricos visíveis mesmo após a transferência do aluno. */
class TeacherRosterService {
    private function eligible(Builder $query): Builder {
        return $query->whereIn('status', [
            EnrollmentStatus::ACTIVE->value,
            EnrollmentStatus::SUSPENDED->value,
            EnrollmentStatus::LOCKED->value,
        ]);
    }

    public function forAssessment(Assessment $assessment): Collection {
        $enrollments = Enrollment::query()
            ->where('class_id', $assessment->class_id)
            ->where(fn (Builder $query) => $this->eligible($query)
                ->orWhereHas('grades', fn (Builder $grades) => $grades->where('assessment_id', $assessment->id)))
            ->with('student')
            ->orderBy('roll_number')
            ->get();

        return $assessment->isRecovery() ? $this->belowAverage($assessment, $enrollments) : $enrollments;
    }

    /**
     * Na recuperação, mantém só quem precisa dela: alunos com média abaixo da mínima na
     * disciplina e no bimestre da avaliação, contando apenas notas publicadas e sem outras
     * recuperações. Quem já tem nota nesta recuperação continua na lista.
     *
     * @param Assessment $assessment
     * @param Collection $enrollments
     *
     * @return Collection
     */
    private function belowAverage(Assessment $assessment, Collection $enrollments): Collection {
        $calculator = app(GradeCalculationService::class);
        $minimum    = $calculator->minimumApproval();
        $studentIds = $enrollments->pluck('student_id');

        $alreadyGraded = Grade::query()
            ->where('assessment_id', $assessment->id)
            ->pluck('student_id')
            ->all();

        $regularGrades = Grade::published()
            ->where('class_id', $assessment->class_id)
            ->where('subject_id', $assessment->subject_id)
            ->where('term', $assessment->gradeTerm())
            ->where('assessment_type', '!=', AssessmentType::RECOVERY->value)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->groupBy('student_id');

        return $enrollments
            ->filter(function (Enrollment $enrollment) use ($alreadyGraded, $regularGrades, $calculator, $minimum): bool {
                if (in_array($enrollment->student_id, $alreadyGraded, false)) {
                    return true;
                }

                $average = $calculator->weightedAverage($regularGrades->get($enrollment->student_id, collect()));

                return $average !== null && $average < $minimum;
            })
            ->values();
    }

    public function forLesson(Lesson $lesson): Collection {
        $recordedStudentIds = Attendance::query()->where('lesson_id', $lesson->id)->pluck('student_id');

        return Enrollment::query()
            ->where('class_id', $lesson->class_id)
            ->where(fn (Builder $query) => $this->eligible($query)
                ->orWhereIn('student_id', $recordedStudentIds))
            ->with('student')
            ->orderBy('roll_number')
            ->get();
    }
}
