<?php

namespace App\Filament\Pages\Teacher;

use App\Enums\AttendanceStatus;
use App\Enums\ClassStatus;
use App\Enums\LessonStatus;
use App\Enums\SchoolYearStatus;
use App\Filament\Pages\Teacher\Concerns\HasTeacherPortalAccess;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\SystemParameter;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Modules\Attendance\Application\RecordTeacherAttendance;
use App\Services\CurrentTeacherService;
use App\Services\TeacherRosterService;
use App\Support\PermissionAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TeacherAttendance extends Page {

    use HasTeacherPortalAccess;

    protected static ?string $navigationLabel                = 'Lançar Frequência';
    protected static ?string $title                          = 'Lançar Frequência';
    protected static ?string $slug                           = 'teacher-attendance';
    protected static string|null|\BackedEnum $navigationIcon = 'fas-user-check';
    protected static string|null|\UnitEnum $navigationGroup  = 'Portal do Professor';
    protected static ?int $navigationSort                    = 6;
    protected static ?string $teacherPortalPermission        = 'teacher.attendance.view';

    public ?int $selectedClassId = null;

    public ?int $selectedSubjectId = null;

    public ?int $selectedLessonId = null;

    public string $selectedDate;

    /**
     * Situação temporária da frequência, indexada por student_id.
     *
     * @var array<int, array{status:string|null}>
     */
    public array $attendanceRows = [];

    /** @var array<int, array<int, string>> */
    public array $statusSelections = [];

    public ?array $saveSummary = null;

    public ?int $selectedStudentId = null;

    /**
     * Inicializa o estado necessário para exibir a página.
     *
     * @return void
     */
    public function mount(): void {
        $this->selectedDate = now()->toDateString();

        $teacher         = $this->currentTeacher();
        $assignments     = $this->teacherAssignments($teacher);
        $requestedClassId = request()->integer('class_id');
        $requestedSubjectId = request()->integer('subject_id');
        $firstAssignment = $assignments->first(fn (TeacherAssignment $assignment) =>
            (int) $assignment->class_id === $requestedClassId
            && (int) $assignment->subject_id === $requestedSubjectId
        ) ?? $assignments->first();

        $this->selectedClassId   = $firstAssignment?->class_id;
        $this->selectedSubjectId = $firstAssignment?->subject_id;
        $this->selectedDate      = $this->defaultDateForSelection();
        $this->selectedLessonId  = $this->firstAvailableLessonId();

        $this->syncAttendanceRows();
    }

    /**
     * Atualiza as disciplinas e a chamada após a seleção de uma turma.
     *
     * @return void
     */
    public function updatedSelectedClassId(): void {
        $this->selectedStudentId = null;
        $this->selectedSubjectId = $this->firstAvailableSubjectId($this->selectedClassId);
        $this->selectedDate = $this->defaultDateForSelection();
        $this->selectedLessonId  = $this->firstAvailableLessonId();
        $this->attendanceRows = [];
        $this->saveSummary       = null;
        $this->syncAttendanceRows();
    }

    /**
     * Atualiza a chamada após a seleção de uma disciplina.
     *
     * @return void
     */
    public function updatedSelectedSubjectId(): void {
        $this->selectedStudentId = null;
        $this->selectedDate = $this->defaultDateForSelection();
        $this->selectedLessonId = $this->firstAvailableLessonId();
        $this->attendanceRows = [];
        $this->saveSummary = null;
        $this->syncAttendanceRows();
    }

    /**
     * Atualiza a frequência quando a data selecionada é alterada.
     *
     * @return void
     */
    public function updatedSelectedDate(): void {
        $this->selectedStudentId = null;
        $this->selectedLessonId = $this->firstAvailableLessonId();
        $this->attendanceRows = [];
        $this->saveSummary = null;
        $this->syncAttendanceRows();
    }

    public function updatedSelectedLessonId(): void {
        $this->selectedStudentId = null;
        $this->attendanceRows = [];
        $this->saveSummary = null;
        $this->syncAttendanceRows();
    }

    /**
     * Retorna o nome da visualização usada pela página.
     *
     * @return string
     */
    public function getView(): string {
        return 'filament.pages.teacher.teacher-attendance';
    }

    public function markAllPresent(): void {
        $data = $this->getPageData();
        abort_unless($data['canEdit'] ?? false, 403);

        foreach ($data['students'] as $student) {
            $this->statusSelections[$student['student_id']] = [AttendanceStatus::PRESENT->value];
        }
    }

    public function togglePresence(int $studentId): void {
        $data = $this->getPageData();
        abort_unless($data['canEdit'] ?? false, 403);
        abort_unless($data['students']->contains(fn (array $row) => (int) $row['student_id'] === $studentId), 403);

        $current = $this->statusSelections[$studentId][0] ?? null;
        $present = in_array($current, [AttendanceStatus::PRESENT->value, AttendanceStatus::LATE->value], true);
        $this->statusSelections[$studentId] = [$present ? AttendanceStatus::ABSENT->value : AttendanceStatus::PRESENT->value];
    }

    public function openStudentDetails(int $studentId): void {
        abort_unless($this->studentDetailsEnabled(), 403);
        $data = $this->getPageData();
        abort_unless($data['students']->contains(fn (array $row) => (int) $row['student_id'] === $studentId), 403);

        $this->selectedStudentId = $studentId;
    }

    public function closeStudentDetails(): void {
        $this->selectedStudentId = null;
    }

    /**
     * Retorna os dados necessários para montar a página.
     *
     * @return array
     */
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
                'lessons'      => [],
                'context'      => null,
                'students'     => collect(),
                'summary'      => $this->emptySummary(),
                'canCreate'    => PermissionAccess::can('teacher.attendance.create'),
                'canUpdate'    => PermissionAccess::can('teacher.attendance.update'),
                'canSubmit'    => false,
                'canEdit'      => false,
                'schoolYearClosed' => false,
                'isBlocked'    => $this->teacherIsBlocked($teacher),
                'saveSummary'  => $this->saveSummary,
                'contextError' => !$teacher ? 'Nenhum professor vinculado ao usuário atual.' : 'Você não possui turmas/disciplina atribuídas.',
                'schoolYear' => SchoolYear::current(),
                'studentDetailsEnabled' => false,
                'studentDetails' => null,
            ];
        }

        $students           = collect();
        $summary            = $this->emptySummary();
        $contextError       = null;
        $isBlocked          = $this->teacherIsBlocked($teacher);
        $schoolYearClosed   = false;
        $hasExistingRecords = false;

        if ($context) {
            $students           = $this->buildStudents($context['lesson'])
                ->map(function (array $row): array {
                    $selected = $this->statusSelections[$row['student_id']] ?? [];
                    $row['status'] = count($selected) === 1 ? $selected[0] : null;
                    return $row;
                });
            $summary            = $this->buildSummary($students);
            $hasExistingRecords = $students->contains(fn (array $row) => !empty($row['attendance_id']));
            $schoolYearClosed   = $context['schoolYear']?->status !== SchoolYearStatus::ACTIVE;
            $schoolYearClosed   = $schoolYearClosed || $context['class']?->status !== ClassStatus::OPEN;
        } elseif ($this->selectedClassId || $this->selectedSubjectId) {
            $contextError = 'Selecione uma aula programada para a turma, disciplina e data informadas.';
        }

        $canCreate = PermissionAccess::can('teacher.attendance.create');
        $canUpdate = PermissionAccess::can('teacher.attendance.update');
        $canEdit = $context !== null && !$isBlocked && !$schoolYearClosed && ($canCreate || $canUpdate)
            && $context['lesson']->date->lte(today())
            && !in_array($context['lesson']->status, [LessonStatus::CANCELLED, LessonStatus::RESCHEDULED], true)
            && $students->isNotEmpty();
        $canSubmit = $canEdit
            && $students->every(fn (array $row) => count($this->statusSelections[$row['student_id']] ?? []) === 1);
        $studentDetailsEnabled = $this->studentDetailsEnabled();
        $studentDetails = $studentDetailsEnabled && $context && $this->selectedStudentId
            ? $this->resolveStudentDetails($context['lesson'], $this->selectedStudentId)
            : null;

        return [
            'teacher'            => $teacher,
            'assignments'        => $assignments,
            'classes'            => $this->classOptions($assignments),
            'subjects'           => $this->subjectOptions($assignments, $this->selectedClassId),
            'lessons'            => $this->lessonOptions(),
            'context'            => $context,
            'students'           => $students,
            'summary'            => $summary,
            'canCreate'          => $canCreate,
            'canUpdate'          => $canUpdate,
            'canSubmit'          => $canSubmit,
            'canEdit'            => $canEdit,
            'isBlocked'          => $isBlocked,
            'schoolYearClosed'   => $schoolYearClosed,
            'hasExistingRecords' => $hasExistingRecords,
            'saveSummary'        => $this->saveSummary,
            'contextError'       => $contextError,
            'schoolYear'         => SchoolYear::current(),
            'studentDetailsEnabled' => $studentDetailsEnabled,
            'studentDetails'     => $studentDetails,
        ];
    }

    /**
     * Salva os registros de frequência dos alunos.
     *
     * @return void
     */
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
        if (!$context) {
            throw ValidationException::withMessages([
                'class_id' => 'Selecione uma turma e uma disciplina vinculadas ao seu cadastro.',
            ]);
        }

        if ($context['schoolYear']?->status !== SchoolYearStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'selectedDate' => 'Não é possível editar frequência em período letivo fechado.',
            ]);
        }

        if ($context['class']?->status !== ClassStatus::OPEN) {
            throw ValidationException::withMessages(['class_id' => 'A turma precisa estar aberta para lançar frequência.']);
        }

        if ($context['lesson']->date->isFuture()) {
            throw ValidationException::withMessages(['selectedLessonId' => 'A chamada não pode ser lançada antes da aula.']);
        }

        if (in_array($context['lesson']->status, [LessonStatus::CANCELLED, LessonStatus::RESCHEDULED], true)) {
            throw ValidationException::withMessages(['selectedLessonId' => 'Aula cancelada ou reagendada não pode receber chamada.']);
        }

        $students = $this->buildStudents($context['lesson']);

        foreach ($students as $student) {
            $selected = $this->statusSelections[$student['student_id']] ?? [];
            $this->attendanceRows[$student['student_id']]['status'] = count($selected) === 1 ? $selected[0] : null;
        }

        if ($students->isEmpty()) {
            throw ValidationException::withMessages([
                'class_id' => 'Nenhum aluno matriculado encontrado para a turma selecionada.',
            ]);
        }

        $canCreate = PermissionAccess::can('teacher.attendance.create');
        $canUpdate = PermissionAccess::can('teacher.attendance.update');
        $this->saveSummary = app(RecordTeacherAttendance::class)->execute(
            students: $students,
            schoolClass: $context['class'],
            subject: $context['subject'],
            lesson: $context['lesson'],
            attendanceRows: $this->attendanceRows,
            canCreate: $canCreate,
            canUpdate: $canUpdate,
            recordedBy: auth()->id(),
        );

        $this->saveSummary['total'] = $this->saveSummary['created'] + $this->saveSummary['updated'];

        $this->attendanceRows = [];
        $this->syncAttendanceRows();
        Notification::make()
            ->title('Frequência salva')
            ->body(sprintf('%d registros criados e %d atualizados.', $this->saveSummary['created'], $this->saveSummary['updated']))
            ->success()
            ->send();
    }

    /**
     * Retorna o professor autenticado no portal.
     *
     * @return Teacher|null
     */
    private function currentTeacher(): ?Teacher {
        return app(CurrentTeacherService::class)->current();
    }

    /**
     * Retorna as atribuições do professor autenticado.
     *
     * @param Teacher|null $teacher
     *
     * @return Collection
     */
    private function teacherAssignments(?Teacher $teacher = null): Collection {
        return app(CurrentTeacherService::class)->currentAssignments($teacher);
    }

    /**
     * Determina se o professor está impedido de realizar lançamentos.
     *
     * @param Teacher|null $teacher
     *
     * @return bool
     */
    private function teacherIsBlocked(?Teacher $teacher): bool {
        if (!$teacher) {
            return true;
        }

        return !$teacher->canAccessOperationally();
    }

    /**
     * Resolve a turma, a disciplina e o período usados na operação.
     *
     * @param Teacher|null $teacher
     * @param Collection $assignments
     * @param bool $strict
     *
     * @return array|null
     */
    private function resolveContext(?Teacher $teacher, Collection $assignments, bool $strict = false): ?array {
        if (!$teacher || $assignments->isEmpty() || !$this->selectedClassId || !$this->selectedSubjectId || !$this->selectedDate || !$this->selectedLessonId) {
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

        $lesson = Lesson::query()
            ->whereKey($this->selectedLessonId)
            ->where('teacher_id', $teacher->id)
            ->where('class_id', $schoolClass->id)
            ->where('subject_id', $subject->id)
            ->where('school_year_id', $schoolClass->school_year_id)
            ->whereDate('date', $this->selectedDate)
            ->first();

        if (!$lesson) {
            return null;
        }

        return [
            'assignment' => $assignment,
            'class'      => $schoolClass,
            'subject'    => $subject,
            'schoolYear' => $schoolClass->schoolYear,
            'lesson'     => $lesson,
            'date'       => $this->selectedDate,
        ];
    }

    /**
     * Monta os dados dos alunos usados nos lançamentos.
     *
     * @param Lesson $lesson
     *
     * @return Collection
     */
    private function buildStudents(Lesson $lesson): Collection {
        $enrollments = app(TeacherRosterService::class)->forLesson($lesson);

        $existing = Attendance::query()
            ->where('lesson_id', $lesson->id)
            ->with('student')
            ->get()
            ->keyBy('student_id');

        return $enrollments->map(function (Enrollment $enrollment) use ($existing) {
            $attendance = $existing->get($enrollment->student_id);
            $student    = $enrollment->student;
            $status = $this->attendanceRows[$enrollment->student_id]['status'] ?? $attendance?->status?->value;

            $this->attendanceRows[$enrollment->student_id] = [
                'status' => $status,
            ];

            return [
                'attendance_id'       => $attendance?->id,
                'student_id'          => $enrollment->student_id,
                'student_name'        => $student?->name ?? '—',
                'photo_url'           => $this->studentPhotoUrl($student?->photo_url),
                'registration_number' => $student?->registration_number ?? '—',
                'roll_number'         => $enrollment->roll_number,
                'enrollment_status'   => $enrollment->status?->label() ?? (string) $enrollment->status,
                'status'              => $status,
            ];
        });
    }

    /**
     * Sincroniza as linhas de frequência com os alunos carregados.
     *
     * @return void
     */
    private function syncAttendanceRows(): void {
        $teacher     = $this->currentTeacher();
        $assignments = $this->teacherAssignments($teacher);
        $context     = $this->resolveContext($teacher, $assignments);

        if (!$context) {
            $this->attendanceRows = [];
            $this->statusSelections = [];
            return;
        }

        $students = $this->buildStudents($context['lesson']);

        $this->attendanceRows = $students->mapWithKeys(function (array $row) {
            return [
                $row['student_id'] => [
                    'status' => $row['status'],
                ],
            ];
        })->all();
        $this->statusSelections = $students->mapWithKeys(fn (array $row) => [
            $row['student_id'] => [$row['status'] ?? AttendanceStatus::PRESENT->value],
        ])->all();
    }

    private function studentDetailsEnabled(): bool {
        return (bool) SystemParameter::read('teacher.student_details_enabled', false);
    }

    private function resolveStudentDetails(Lesson $lesson, int $studentId): ?array {
        $enrollment = app(TeacherRosterService::class)->forLesson($lesson)
            ->first(fn (Enrollment $row) => (int) $row->student_id === $studentId);
        $student = $enrollment?->student;

        if (!$student) {
            return null;
        }

        return [
            'name' => $student->name,
            'registration' => $student->registration_number,
            'photo' => $this->studentPhotoUrl($student->photo_url),
            'class' => $lesson->schoolClass?->name,
            'rollNumber' => $enrollment->roll_number,
            'enrollmentStatus' => $enrollment->status?->label() ?? '—',
            'birthDate' => $student->birth_date?->format('d/m/Y'),
            'guardian' => $student->guardian_main,
            'guardianPhone' => $student->guardian_phone,
        ];
    }

    private function studentPhotoUrl(?string $path): ?string {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Retorna as turmas disponíveis para o lançamento de frequência.
     *
     * @param Collection $assignments
     *
     * @return array
     */
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

    /**
     * Retorna as disciplinas disponíveis na turma selecionada.
     *
     * @param Collection $assignments
     * @param int|null $classId
     *
     * @return array
     */
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

    /**
     * Retorna o identificador da primeira disciplina disponível.
     *
     * @param int|null $classId
     *
     * @return int|null
     */
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

    /** Aulas concretas da data escolhida; cada chamada pertence a uma aula. */
    private function lessonOptions(): array {
        $teacher = $this->currentTeacher();
        if (!$teacher || !$this->selectedClassId || !$this->selectedSubjectId || !$this->selectedDate) {
            return [];
        }

        $assignment = $this->teacherAssignments($teacher)->first(fn (TeacherAssignment $item) =>
            (int) $item->class_id === (int) $this->selectedClassId
            && (int) $item->subject_id === (int) $this->selectedSubjectId
        );
        if (!$assignment) {
            return [];
        }

        return Lesson::query()
            ->where('teacher_id', $teacher->id)
            ->where('class_id', $assignment->class_id)
            ->where('subject_id', $assignment->subject_id)
            ->where('school_year_id', $assignment->schoolClass?->school_year_id)
            ->whereDate('date', $this->selectedDate)
            ->orderBy('start_time')
            ->get()
            ->mapWithKeys(fn (Lesson $lesson) => [
                $lesson->id => $lesson->start_time?->format('H:i') . '–' . $lesson->end_time?->format('H:i'),
            ])
            ->all();
    }

    private function firstAvailableLessonId(): ?int {
        $options = $this->lessonOptions();

        return $options ? (int) array_key_first($options) : null;
    }

    private function defaultDateForSelection(): string {
        $teacher = $this->currentTeacher();
        $assignment = $this->teacherAssignments($teacher)->first(fn (TeacherAssignment $item) =>
            (int) $item->class_id === (int) $this->selectedClassId
            && (int) $item->subject_id === (int) $this->selectedSubjectId
        );

        if (!$teacher || !$assignment || $assignment->schoolClass?->schoolYear?->status === SchoolYearStatus::ACTIVE) {
            return today()->toDateString();
        }

        return Lesson::query()
            ->where('teacher_id', $teacher->id)
            ->where('class_id', $assignment->class_id)
            ->where('subject_id', $assignment->subject_id)
            ->where('school_year_id', $assignment->schoolClass?->school_year_id)
            ->orderByDesc('date')
            ->first(['date'])?->date?->toDateString() ?? today()->toDateString();
    }

    /**
     * Retorna o resumo calculado para exibição.
     *
     * @param array|Collection $students
     *
     * @return array
     */
    private function buildSummary(array|Collection $students): array {
        $collection = collect($students);

        return [
            'total'   => $collection->count(),
            'present' => $collection->filter(fn (array $row) => in_array($row['status'], [AttendanceStatus::PRESENT->value, AttendanceStatus::LATE->value], true))->count(),
            'absent'  => $collection->filter(fn (array $row) => in_array($row['status'], [AttendanceStatus::ABSENT->value, AttendanceStatus::EXCUSED->value], true))->count(),
        ];
    }

    /**
     * Retorna a estrutura vazia do resumo.
     *
     * @return array
     */
    private function emptySummary(): array {
        return [
            'total'   => 0,
            'present' => 0,
            'absent'  => 0,
        ];
    }

}
