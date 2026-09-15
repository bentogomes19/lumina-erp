<?php

namespace App\Services\Domain;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\EnrollmentDocument;
use App\Models\EnrollmentLog;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\SchoolHoliday;
use App\Models\SchoolYear;
use App\Models\SchoolYearTerm;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeletionGuard {

    /**
     * Retorna descrições de todos os vínculos que impedem a exclusão física.
     *
     * @return Collection<int, string>
     */
    public function blockingLinks(Model $record): Collection {

        if ($record->getKey() === null) {
            return collect();
        }

        return match (true) {
            $record instanceof Teacher     => $this->teacherLinks($record),
            $record instanceof Student     => $this->studentLinks($record),
            $record instanceof Subject     => $this->subjectLinks($record),
            $record instanceof SchoolClass => $this->schoolClassLinks($record),
            $record instanceof SchoolYear  => $this->schoolYearLinks($record),
            $record instanceof Enrollment  => $this->enrollmentLinks($record),
            default => collect(),
        };
    }

    /** @return Collection<int, string> */
    private function teacherLinks(Teacher $teacher): Collection {
        return $this->links([
            [TeacherAssignment::query()->where('teacher_id', $teacher->getKey())->count(), 'atribuição', 'atribuições'],
            [Lesson::withTrashed()->where('teacher_id', $teacher->getKey())->count(), 'aula', 'aulas'],
            [Assessment::query()->where('teacher_id', $teacher->getKey())->count(), 'avaliação', 'avaliações'],
        ]);
    }

    /** @return Collection<int, string> */
    private function studentLinks(Student $student): Collection {
        return $this->links([
            [Enrollment::withTrashed()->where('student_id', $student->getKey())->count(), 'matrícula', 'matrículas'],
            [Grade::query()->where('student_id', $student->getKey())->count(), 'nota lançada', 'notas lançadas'],
            [Attendance::query()->where('student_id', $student->getKey())->count(), 'registro de frequência', 'registros de frequência'],
        ]);
    }

    /** @return Collection<int, string> */
    private function subjectLinks(Subject $subject): Collection {
        return $this->links([
            [DB::table('grade_level_subject')->where('subject_id', $subject->getKey())->count(), 'nível/série', 'níveis/séries'],
            [DB::table('class_subjects')->where('subject_id', $subject->getKey())->count(), 'turma', 'turmas'],
            [TeacherAssignment::query()->where('subject_id', $subject->getKey())->count(), 'atribuição', 'atribuições'],
            [Assessment::query()->where('subject_id', $subject->getKey())->count(), 'avaliação', 'avaliações'],
            [Grade::query()->where('subject_id', $subject->getKey())->count(), 'nota lançada', 'notas lançadas'],
            [Attendance::query()->where('subject_id', $subject->getKey())->count(), 'registro de frequência', 'registros de frequência'],
        ]);
    }

    /** @return Collection<int, string> */
    private function schoolClassLinks(SchoolClass $schoolClass): Collection {
        $classId = $schoolClass->getKey();

        return $this->links([
            [Enrollment::withTrashed()->where('class_id', $classId)->count(), 'matrícula', 'matrículas'],
            [DB::table('enrollments')->where('class_id', $classId)->distinct()->count('student_id'), 'aluno', 'alunos'],
            [TeacherAssignment::query()->where('class_id', $classId)->count(), 'atribuição', 'atribuições'],
            [DB::table('class_subjects')->where('class_id', $classId)->count(), 'disciplina', 'disciplinas'],
            [Grade::query()->where('class_id', $classId)->count(), 'nota lançada', 'notas lançadas'],
            [Attendance::query()->where('class_id', $classId)->count(), 'registro de frequência', 'registros de frequência'],
            [Assessment::query()->where('class_id', $classId)->count(), 'avaliação', 'avaliações'],
        ]);
    }

    /** @return Collection<int, string> */
    private function schoolYearLinks(SchoolYear $schoolYear): Collection {
        return $this->links([
            [SchoolYearTerm::query()->where('school_year_id', $schoolYear->getKey())->count(), 'período letivo', 'períodos letivos'],
            [SchoolClass::withTrashed()->where('school_year_id', $schoolYear->getKey())->count(), 'turma', 'turmas'],
            [Enrollment::withTrashed()->where('school_year_id', $schoolYear->getKey())->count(), 'matrícula', 'matrículas'],
            [SchoolHoliday::query()->where('school_year_id', $schoolYear->getKey())->count(), 'feriado/recesso', 'feriados/recessos'],
        ]);
    }

    /** @return Collection<int, string> */
    private function enrollmentLinks(Enrollment $enrollment): Collection {
        return $this->links([
            [EnrollmentLog::query()->where('enrollment_id', $enrollment->getKey())->count(), 'log de auditoria', 'logs de auditoria'],
            [Grade::query()->where('enrollment_id', $enrollment->getKey())->count(), 'nota lançada', 'notas lançadas'],
            [EnrollmentDocument::query()->where('enrollment_id', $enrollment->getKey())->count(), 'documento', 'documentos'],
        ]);
    }

    /**
     * @param  array<int, array{0: int, 1: string, 2: string}>  $counts
     * @return Collection<int, string>
     */
    private function links(array $counts): Collection {
        return collect($counts)->filter(fn (array $link): bool => $link[0] > 0)->map(fn (array $link): string => sprintf('%d %s', $link[0], $link[0] === 1 ? $link[1] : $link[2],))->values();
    }
}
