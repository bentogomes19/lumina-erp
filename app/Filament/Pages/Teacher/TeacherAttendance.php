<?php

namespace App\Filament\Pages\Teacher;

use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\SchoolYearStatus;
use App\Enums\TeacherStatus;
use App\Filament\Pages\Teacher\Concerns\HasTeacherPortalAccess;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Services\CurrentTeacherService;
use App\Support\PermissionAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherAttendance extends Page {
    use HasTeacherPortalAccess;

    protected static ?string $navigationLabel = 'Lançar Frequência';
    protected static ?string $title = 'Lançar Frequência';
    protected static ?string $slug = 'teacher-attendance';
    protected static string|null|\BackedEnum $navigationIcon = 'fas-user-check';
    protected static string|null|\UnitEnum $navigationGroup = 'Portal do Professor';
    protected static ?int $navigationSort = 6;
    protected static ?string $teacherPortalPermission = 'teacher.attendance.view';

    public ?int $selectedClassId = null;

    public ?int $selectedSubjectId = null;

    public string $selectedDate;

    /**
     * Status temporários da frequência, indexados por student_id.
     *
     * @var array<int, array{present:bool}>
     */
    public array $attendanceRows = [];

    public ?array $saveSummary = null;

    public function mount(): void {
        $this->selectedDate = now()->toDateString();

        $teacher         = $this->currentTeacher();
        $assignments     = $this->teacherAssignments($teacher);
        $firstAssignment = $assignments->first();

        $this->selectedClassId   = $firstAssignment?->class_id;
        $this->selectedSubjectId = $firstAssignment?->subject_id;

        $this->syncAttendanceRows();
    }

    public function updatedSelectedClassId(): void {
        $this->selectedSubjectId = $this->firstAvailableSubjectId($this->selectedClassId);
        $this->saveSummary = null;
        $this->syncAttendanceRows();
    }

    public function updatedSelectedSubjectId(): void {
        $this->saveSummary = null;
        $this->syncAttendanceRows();
    }

    public function updatedSelectedDate(): void {
        $this->saveSummary = null;
        $this->syncAttendanceRows();
    }

    public function getView(): string {
        return 'filament.pages.teacher.teacher-attendance';
    }

    public function getPageData(): array {
        $teacher     = $this->currentTeacher();
        $assignments = $this->teacherAssignments($teacher);
        $context     = $this->resolveContext($teacher, $assignments);

        if (!$teacher || $assignments->isEmpty()) {
            return [
                'teacher'      => $teacher,
                'assignments'  => $assignments,
                'classes'      => [],
                'subjects'     => [],
                'context'      => null,
                'students'     => collect(),
                'summary'      => $this->emptySummary(),
                'canCreate'    => PermissionAccess::can('teacher.attendance.create'),
                'canUpdate'    => PermissionAccess::can('teacher.attendance.update'),
                'canSubmit'    => false,
                'isBlocked'    => $this->teacherIsBlocked($teacher),
                'saveSummary'  => $this->saveSummary,
                'contextError' => !$teacher ? 'Nenhum professor vinculado ao usuário atual.' : 'Você não possui turmas/disciplina atribuídas.',
            ];
        }

        $students           = collect();
        $summary            = $this->emptySummary();
        $contextError       = null;
        $isBlocked          = $this->teacherIsBlocked($teacher);
        $schoolYearClosed   = false;
        $hasExistingRecords = false;

        if ($context) {
            $students           = $this->buildStudents($context['class']->id, $context['subject']->id, $context['date']);
            $summary            = $this->buildSummary($students);
            $hasExistingRecords = $students->contains(fn (array $row) => ! empty($row['attendance_id']));
            $schoolYearClosed   = $context['schoolYear']?->status === SchoolYearStatus::CLOSED;
        } elseif ($this->selectedClassId || $this->selectedSubjectId) {
            $contextError = 'A turma e a disciplina selecionadas precisam estar vinculadas ao seu cadastro.';
        }

        $canCreate = PermissionAccess::can('teacher.attendance.create');
        $canUpdate = PermissionAccess::can('teacher.attendance.update');
        $canSubmit = !$isBlocked && !$schoolYearClosed && ($canCreate || $canUpdate) && !empty($students);

        return [
            'teacher'            => $teacher,
            'assignments'        => $assignments,
            'classes'            => $this->classOptions($assignments),
            'subjects'           => $this->subjectOptions($assignments, $this->selectedClassId),
            'context'            => $context,
            'students'           => $students,
            'summary'            => $summary,
            'canCreate'          => $canCreate,
            'canUpdate'          => $canUpdate,
            'canSubmit'          => $canSubmit,
            'isBlocked'          => $isBlocked,
            'schoolYearClosed'   => $schoolYearClosed,
            'hasExistingRecords' => $hasExistingRecords,
            'saveSummary'        => $this->saveSummary,
            'contextError'       => $contextError,
        ];
    }

    public function saveAttendance(): void {
        $teacher     = $this->currentTeacher();
        $assignments = $this->teacherAssignments($teacher);

        if (!$teacher) {
            throw ValidationException::withMessages([
                'teacher' => 'Nenhum professor vinculado ao usuário atual.',
            ]);
        }

        if ($this->teacherIsBlocked($teacher)) {
            throw ValidationException::withMessages([
                'teacher' => 'Professor afastado, inativo ou desligado não pode lançar frequência.',
            ]);
        }

        $context = $this->resolveContext($teacher, $assignments, true);
        if (! $context) {
            throw ValidationException::withMessages([
                'class_id' => 'Selecione uma turma e uma disciplina vinculadas ao seu cadastro.',
            ]);
        }

        if ($context['schoolYear']?->status === SchoolYearStatus::CLOSED) {
            throw ValidationException::withMessages([
                'selectedDate' => 'Não é possível editar frequência em período letivo fechado.',
            ]);
        }

        $students = $this->buildStudents($context['class']->id, $context['subject']->id, $context['date']);

        if ($students->isEmpty()) {
            throw ValidationException::withMessages([
                'class_id' => 'Nenhum aluno matriculado encontrado para a turma selecionada.',
            ]);
        }

        $canCreate = PermissionAccess::can('teacher.attendance.create');
        $canUpdate = PermissionAccess::can('teacher.attendance.update');
        $created   = 0;
        $updated   = 0;

        DB::transaction(function () use ($students, $context, $teacher, $canCreate, $canUpdate, &$created, &$updated) {
            foreach ($students as $student) {
                $studentId = (int) $student['student_id'];
                $isPresent = $this->attendanceRows[$studentId]['present'] ?? ($student['status'] === AttendanceStatus::PRESENT->value);
                $status    = $isPresent ? AttendanceStatus::PRESENT->value : AttendanceStatus::ABSENT->value;

                $attributes = [
                    'student_id' => $studentId,
                    'class_id'   => $context['class']->id,
                    'subject_id' => $context['subject']->id,
                    'date'       => $context['date'],
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
                        'recorded_by' => auth()->id(),
                    ]);

                    $updated++;
                    continue;
                }

                if (! $canCreate) {
                    throw ValidationException::withMessages(['teacher' => 'Você não tem permissão para criar frequência nova.',]);
                }

                Attendance::create($attributes + [
                    'status'      => $status,
                    'recorded_by' => auth()->id(),
                ]);

                $created++;
            }
        });

        $this->saveSummary = [
            'created' => $created,
            'updated' => $updated,
            'total'   => $created + $updated,
            'present' => $students->filter(fn (array $row) => ($this->attendanceRows[$row['student_id']]['present'] ?? ($row['status'] === AttendanceStatus::PRESENT->value)))->count(),
            'absent'  => $students->filter(fn (array $row) => ! ($this->attendanceRows[$row['student_id']]['present'] ?? ($row['status'] === AttendanceStatus::PRESENT->value)))->count(),
        ];

        $this->syncAttendanceRows();
        Notification::make()
            ->title('Frequência salva')
            ->body(sprintf('%d registros criados e %d atualizados.', $created, $updated))
            ->success()
            ->send();
    }

    private function currentTeacher(): ?Teacher {
        return app(CurrentTeacherService::class)->current();
    }

    private function teacherAssignments(?Teacher $teacher = null): Collection {
        return app(CurrentTeacherService::class)->assignments($teacher);
    }

    private function teacherIsBlocked(?Teacher $teacher): bool {
        if (!$teacher) {
            return true;
        }

        return in_array($teacher->status, [
            TeacherStatus::SABBATICAL,
            TeacherStatus::INACTIVE,
            TeacherStatus::TERMINATED,
        ], true);
    }

    private function resolveContext(?Teacher $teacher, Collection $assignments, bool $strict = false): ?array {
        if (!$teacher || $assignments->isEmpty() || ! $this->selectedClassId || !$this->selectedSubjectId || !$this->selectedDate) {
            return null;
        }

        $assignment = $assignments->first(function (TeacherAssignment $item) {
            return (int)$item->class_id === (int)$this->selectedClassId && (int)$item->subject_id === (int)$this->selectedSubjectId;
        });

        if (!$assignment) {
            return null;
        }

        $schoolClass = $assignment->schoolClass()->with('schoolYear')->first();
        $subject     = $assignment->subject()->first();

        if (!$schoolClass || !$subject) {
            return null;
        }

        return [
            'assignment' => $assignment,
            'class'      => $schoolClass,
            'subject'    => $subject,
            'schoolYear' => $schoolClass->schoolYear,
            'date'       => $this->selectedDate,
        ];
    }

    private function buildStudents(int $classId, int $subjectId, string $date): Collection {
        $enrollments = Enrollment::query()
            ->where('class_id', $classId)
            ->whereIn('status', [
                EnrollmentStatus::ACTIVE->value,
                EnrollmentStatus::SUSPENDED->value,
                EnrollmentStatus::LOCKED->value,
            ])
            ->with(['student'])
            ->orderBy('roll_number')
            ->get();

        $existing = Attendance::query()
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->whereDate('date', $date)
            ->with('student')
            ->get()
            ->keyBy('student_id');

        return $enrollments->map(function (Enrollment $enrollment) use ($existing) {
            $attendance = $existing->get($enrollment->student_id);
            $student    = $enrollment->student;
            $status     = $attendance?->status?->value ?? AttendanceStatus::PRESENT->value;
            $isPresent  = $this->attendanceRows[$enrollment->student_id]['present'] ?? ($status === AttendanceStatus::PRESENT->value);

            $this->attendanceRows[$enrollment->student_id] = [
                'present' => $isPresent,
            ];

            return [
                'attendance_id'       => $attendance?->id,
                'student_id'          => $enrollment->student_id,
                'student_name'        => $student?->name ?? '—',
                'registration_number' => $student?->registration_number ?? '—',
                'roll_number'         => $enrollment->roll_number,
                'enrollment_status'   => $enrollment->status?->label() ?? (string) $enrollment->status,
                'status'              => $status,
                'is_present'          => $isPresent,
            ];
        });
    }

    private function syncAttendanceRows(): void {
        $teacher     = $this->currentTeacher();
        $assignments = $this->teacherAssignments($teacher);
        $context     = $this->resolveContext($teacher, $assignments);

        if (!$context) {
            $this->attendanceRows = [];
            return;
        }

        $students = $this->buildStudents($context['class']->id, $context['subject']->id, $context['date']);

        $this->attendanceRows = $students->mapWithKeys(function (array $row) {
            return [
                $row['student_id'] => [
                    'present' => $row['is_present'],
                ],
            ];
        })->all();
    }

    private function classOptions(Collection $assignments): array {
        return $assignments
            ->pluck('schoolClass')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->mapWithKeys(fn (SchoolClass $class) => [
                $class->id => trim($class->name . ' • ' . ($class->schoolYear?->year ?? $class->schoolYear?->name ?? 'Sem período')),
            ])
            ->all();
    }

    private function subjectOptions(Collection $assignments, ?int $classId = null): array {
        $filtered = $assignments;

        if ($classId) {
            $filtered = $filtered->where('class_id', $classId);
        }

        return $filtered
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->mapWithKeys(fn ($subject) => [$subject->id => $subject->name])
            ->all();
    }

    private function firstAvailableSubjectId(?int $classId): ?int {
        if (!$classId) {
            return null;
        }

        $assignments = $this->teacherAssignments();
        return $assignments
            ->where('class_id', $classId)
            ->sortBy('subject.name')
            ->first()
            ?->subject_id;
    }

    private function attendanceStatusOptions(): array {
        return [
            AttendanceStatus::PRESENT->value => AttendanceStatus::PRESENT->label(),
            AttendanceStatus::ABSENT->value  => AttendanceStatus::ABSENT->label(),
        ];
    }

    private function attendanceStatusColor(string $status): string {
        return match ($status) {
            AttendanceStatus::PRESENT->value => 'success',
            AttendanceStatus::ABSENT->value  => 'danger',
            default => 'gray',
        };
    }

    private function buildSummary(array|Collection $students): array {
        $collection = collect($students);

        return [
            'total'   => $collection->count(),
            'present' => $collection->where('status', AttendanceStatus::PRESENT->value)->count(),
            'absent'  => $collection->where('status', AttendanceStatus::ABSENT->value)->count(),
        ];
    }

    private function emptySummary(): array {
        return [
            'total'   => 0,
            'present' => 0,
            'absent'  => 0,
        ];
    }

}
