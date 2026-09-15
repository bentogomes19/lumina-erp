<?php

namespace App\Modules\Attendance\Application;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Subject;
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
     * @param array<int, array{present:bool}> $attendanceRows
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
        string $date,
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
            $date,
            $attendanceRows,
            $canCreate,
            $canUpdate,
            $recordedBy,
            &$created,
            &$updated,
        ): void {
            foreach ($students as $student) {
                $studentId = (int) $student['student_id'];
                $isPresent = $attendanceRows[$studentId]['present'] ?? ($student['status'] === AttendanceStatus::PRESENT->value);
                $status = $isPresent ? AttendanceStatus::PRESENT->value : AttendanceStatus::ABSENT->value;

                $attributes = [
                    'student_id' => $studentId,
                    'class_id'   => $schoolClass->id,
                    'subject_id' => $subject->id,
                    'date'       => $date,
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
                    'status'      => $status,
                    'recorded_by' => $recordedBy,
                ]);

                $created++;
            }
        });

        $present = $students->filter(fn (array $student): bool => (bool) ($attendanceRows[$student['student_id']]['present'] ?? ($student['status'] === AttendanceStatus::PRESENT->value)))->count();

        return [
            'created' => $created,
            'updated' => $updated,
            'present' => $present,
            'absent'  => $students->count() - $present,
        ];
    }
}
