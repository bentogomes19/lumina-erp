<?php

namespace App\Modules\Attendance\Application;

use App\Enums\AttendanceStatus;
use App\Enums\LessonStatus;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Lesson;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Persiste a frequência lançada pelo professor fora da camada Filament.
 */
final class RecordTeacherAttendance {

    /**
     * @param Collection<int, array{student_id:int, status:string}> $students
     * @param SchoolClass $schoolClass
     * @param Subject $subject
     * @param string $date
     * @param array<int, array{status:string|null}> $attendanceRows
     * @param bool $canCreate
     * @param bool $canUpdate
     * @param int|null $recordedBy
     *
     * @return array{created:int, updated:int, present:int, absent:int}
     */
    public function execute(
        Collection $students,
        SchoolClass $schoolClass,
        Subject $subject,
        Lesson $lesson,
        array $attendanceRows,
        bool $canCreate,
        bool $canUpdate,
        ?int $recordedBy,
    ): array {
        $created = 0;
        $updated = 0;

        DB::transaction(function () use (
            $students,
            $schoolClass,
            $subject,
            $lesson,
            $attendanceRows,
            $canCreate,
            $canUpdate,
            $recordedBy,
            &$created,
            &$updated,
        ): void {
            $lockedLesson = Lesson::query()->whereKey($lesson->id)->lockForUpdate()->firstOrFail();
            if ((int) $lockedLesson->class_id !== (int) $schoolClass->id
                || (int) $lockedLesson->subject_id !== (int) $subject->id) {
                throw ValidationException::withMessages(['lesson' => 'A aula não pertence à turma e disciplina selecionadas.']);
            }

            if (in_array($lockedLesson->status, [LessonStatus::CANCELLED, LessonStatus::RESCHEDULED], true)) {
                throw ValidationException::withMessages(['lesson' => 'Aula cancelada ou reagendada não pode receber chamada.']);
            }

            foreach ($students as $student) {
                $studentId = (int) $student['student_id'];
                $status = $attendanceRows[$studentId]['status'] ?? null;
                if (!in_array($status, array_keys(AttendanceStatus::options()), true)) {
                    throw ValidationException::withMessages([
                        "attendanceRows.{$studentId}.status" => 'Selecione a situação de frequência do aluno.',
                    ]);
                }

                $attributes = [
                    'student_id' => $studentId,
                    'lesson_id'  => $lockedLesson->id,
                ];

                $existing = Attendance::query()
                    ->where($attributes)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    if (!$canUpdate) {
                        throw ValidationException::withMessages([
                            'teacher' => 'Você não tem permissão para atualizar frequência existente.',
                        ]);
                    }

                    $existing->update([
                        'status'      => $status,
                        'recorded_by' => $recordedBy,
                    ]);

                    $updated++;
                    continue;
                }

                if (!$canCreate) {
                    throw ValidationException::withMessages([
                        'teacher' => 'Você não tem permissão para criar frequência nova.',
                    ]);
                }

                Attendance::create($attributes + [
                    'class_id'    => $schoolClass->id,
                    'subject_id'  => $subject->id,
                    'date'        => $lockedLesson->date->toDateString(),
                    'status'      => $status,
                    'recorded_by' => $recordedBy,
                ]);

                $created++;
            }

            $lockedLesson->markAttendanceTaken($recordedBy);
        });

        $present = $students->filter(fn (array $student): bool =>
            AttendanceStatus::from($attendanceRows[$student['student_id']]['status'])->countsAsPresent()
        )->count();

        return [
            'created' => $created,
            'updated' => $updated,
            'present' => $present,
            'absent'  => $students->count() - $present,
        ];
    }
}
