<?php

namespace App\Filament\Pages\Teacher;

use App\Enums\AssessmentType;
use App\Enums\ClassStatus;
use App\Enums\SchoolYearStatus;
use App\Enums\Term;
use App\Filament\Pages\Teacher\Concerns\HasTeacherPortalAccess;
use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\SchoolYear;
use App\Modules\Assessments\Application\RecordTeacherGrades;
use App\Services\CurrentTeacherService;
use App\Services\GradeCalculationService;
use App\Services\TeacherRosterService;
use App\Support\PermissionAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TeacherGrades extends Page {

    use HasTeacherPortalAccess;

    protected static ?string $navigationLabel                = 'Lançar Notas';
    protected static ?string $title                          = 'Lançar Notas';
    protected static ?string $slug                           = 'teacher-grades';
    protected static string|null|\BackedEnum $navigationIcon = 'fas-pen-to-square';
    protected static string|null|\UnitEnum $navigationGroup  = 'Portal do Professor';
    protected static ?int $navigationSort                    = 5;
    protected static ?string $teacherPortalPermission        = 'teacher.grades.view';

    public ?int $selectedClassId      = null;
    public ?int $selectedSubjectId    = null;
    public ?int $selectedAssessmentId = null;

    /**
     * @var array<int, array{score:float|int|null, comment:string|null}>
     */
    public array $gradeRows = [];

    public ?array $saveSummary = null;

    /**
     * Determina se a página deve ser registrada na navegação.
     *
     * @return bool
     */
    public static function shouldRegisterNavigation(): bool {
        return PermissionAccess::can('teacher.grades.view');
    }

    /**
     * Determina se o usuário atual pode acessar a página.
     *
     * @return bool
     */
    public static function canAccess(): bool {
        return PermissionAccess::can('teacher.grades.view');
    }

    /**
     * Inicializa o estado necessário para exibir a página.
     *
     * @return void
     */
    public function mount(): void {
        $teacher         = $this->currentTeacher();
        $assignments     = $this->teacherAssignments($teacher);
        $requestedClassId = request()->integer('class_id');
        $requestedSubjectId = request()->integer('subject_id');
        $firstAssignment = $assignments->first(fn (TeacherAssignment $assignment) =>
            (int) $assignment->class_id === $requestedClassId
            && (int) $assignment->subject_id === $requestedSubjectId
        ) ?? $assignments->first();

        $this->selectedClassId      = $firstAssignment?->class_id;
        $this->selectedSubjectId    = $firstAssignment?->subject_id;
        $this->selectedAssessmentId = $this->firstAvailableAssessmentId($teacher, $this->selectedClassId, $this->selectedSubjectId);

        $this->syncGradeRows();
    }

    /**
     * Atualiza as disciplinas e avaliações após a seleção de uma turma.
     *
     * @return void
     */
    public function updatedSelectedClassId(): void {
        $this->gradeRows = [];
        $this->selectedSubjectId    = $this->firstAvailableSubjectId($this->selectedClassId);
        $this->selectedAssessmentId = $this->firstAvailableAssessmentId($this->currentTeacher(), $this->selectedClassId, $this->selectedSubjectId);
        $this->saveSummary          = null;
        $this->syncGradeRows();
    }

    /**
     * Atualiza as avaliações após a seleção de uma disciplina.
     *
     * @return void
     */
    public function updatedSelectedSubjectId(): void {
        $this->gradeRows = [];
        $this->selectedAssessmentId = $this->firstAvailableAssessmentId($this->currentTeacher(), $this->selectedClassId, $this->selectedSubjectId);
        $this->saveSummary          = null;
        $this->syncGradeRows();
    }

    /**
     * Carrega as notas após a seleção de uma avaliação.
     *
     * @return void
     */
    public function updatedSelectedAssessmentId(): void {
        $this->gradeRows = [];
        $this->saveSummary = null;
        $this->syncGradeRows();
    }

    /**
     * Retorna o nome da visualização usada pela página.
     *
     * @return string
     */
    public function getView(): string {
        return 'filament.pages.teacher.teacher-grades';
    }

    /**
     * Retorna os dados necessários para montar a página.
     *
     * @return array
     */
    public function getPageData(): array {
        $teacher     = $this->currentTeacher();
        $assignments = $this->teacherAssignments($teacher);
        $minimumGrade = app(GradeCalculationService::class)->minimumApproval();
        $context     = $this->resolveContext($teacher, $assignments);

        if (!$teacher || $assignments->isEmpty()) {
            return [
                'teacher'      => $teacher,
                'assignments'  => $assignments,
                'classes'      => [],
                'subjects'     => [],
                'assessments'  => [],
                'context'      => null,
                'students'     => collect(),
                'summary'      => $this->emptySummary(),
                'canSave'      => false,
                'canPublish'   => false,
                'isBlocked'    => $this->teacherIsBlocked($teacher),
                'assessmentClosed' => false,
                'schoolYearClosed' => false,
                'hasLockedGrades' => false,
                'contextError' => !$teacher ? 'Nenhum professor vinculado ao usuário atual.' : 'Você não possui turmas ou disciplinas atribuídas.',
                'saveSummary'  => $this->saveSummary,
                'minimumGrade' => $minimumGrade,
            ];
        }

        $students         = collect();
        $summary          = $this->emptySummary();
        $contextError     = null;
        $isBlocked        = $this->teacherIsBlocked($teacher);
        $assessmentClosed = false;
        $schoolYearClosed = false;
        $hasLockedGrades  = false;

        if ($context) {
            $students         = $this->buildStudents($context['assessment']);
            $summary          = $this->buildSummary($students);
            $assessmentClosed = $context['assessment']->isClosed();
            $schoolYearClosed = $context['schoolYear']?->status !== SchoolYearStatus::ACTIVE;
            $schoolYearClosed = $schoolYearClosed || $context['class']?->status !== ClassStatus::OPEN;
            $hasLockedGrades  = Grade::query()
                ->where('assessment_id', $context['assessment']->id)
                ->whereNotNull('locked_at')
                ->exists();
        } elseif ($this->selectedClassId || $this->selectedSubjectId || $this->selectedAssessmentId) {
            $contextError = 'Selecione uma avaliação vinculada ao seu cadastro.';
        }

        $canSavePermission    = PermissionAccess::can('teacher.grades.create') || PermissionAccess::can('teacher.grades.update');
        $canPublishPermission = PermissionAccess::can('teacher.grades.publish');

        $canSave = $canSavePermission
            && !$isBlocked
            && !$assessmentClosed
            && !$schoolYearClosed
            && !$hasLockedGrades
            && $students->isNotEmpty();

        $canPublish = $canPublishPermission
            && !$isBlocked
            && !$assessmentClosed
            && !$schoolYearClosed
            && !$hasLockedGrades
            && $students->isNotEmpty();

        return [
            'teacher'          => $teacher,
            'assignments'      => $assignments,
            'classes'          => $this->classOptions($assignments),
            'subjects'         => $this->subjectOptions($assignments, $this->selectedClassId),
            'assessments'      => $this->assessmentOptions($teacher, $this->selectedClassId, $this->selectedSubjectId),
            'context'          => $context,
            'students'         => $students,
            'summary'          => $summary,
            'canSave'          => $canSave,
            'canPublish'       => $canPublish,
            'isBlocked'        => $isBlocked,
            'assessmentClosed' => $assessmentClosed,
            'schoolYearClosed' => $schoolYearClosed,
            'hasLockedGrades'  => $hasLockedGrades,
            'contextError'     => $contextError,
            'saveSummary'      => $this->saveSummary,
            'minimumGrade'     => $minimumGrade,
        ];
    }

    /**
     * Salva as notas como rascunho.
     *
     * @return void
     */
    public function saveDraft(): void {
        $this->saveGrades(false);
    }

    /**
     * Publica as notas lançadas na avaliação.
     *
     * @return void
     */
    public function publishGrades(): void {
        $this->saveGrades(true);
    }

    /**
     * Valida e salva as notas lançadas na avaliação.
     *
     * @param bool $publish
     *
     * @return void
     */
    private function saveGrades(bool $publish): void {
        $teacher     = $this->currentTeacher();
        $assignments = $this->teacherAssignments($teacher);

        if (!$teacher) {
            throw ValidationException::withMessages([
                'teacher' => 'Nenhum professor vinculado ao usuário atual.',
            ]);
        }

        if ($this->teacherIsBlocked($teacher)) {
            throw ValidationException::withMessages([
                'teacher' => 'Professor afastado, inativo ou desligado não pode lançar notas.',
            ]);
        }

        $context = $this->resolveContext($teacher, $assignments);

        if (!$context) {
            throw ValidationException::withMessages([
                'assessment' => 'Selecione uma avaliação válida.',
            ]);
        }

        $assessment = $context['assessment'];

        if ($assessment->isClosed()) {
            throw ValidationException::withMessages([
                'assessment' => 'Avaliação fechada não pode receber notas.',
            ]);
        }

        if ($context['schoolYear']?->status !== SchoolYearStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'assessment' => 'Período letivo fechado bloqueia edição de notas.',
            ]);
        }

        if ($context['class']?->status !== ClassStatus::OPEN) {
            throw ValidationException::withMessages(['class_id' => 'A turma precisa estar aberta para lançar notas.']);
        }

        $students = $this->buildStudents($assessment);

        if ($students->isEmpty()) {
            throw ValidationException::withMessages([
                'assessment' => 'Nenhum aluno matriculado encontrado para a avaliação selecionada.',
            ]);
        }

        if (!PermissionAccess::can('teacher.grades.create') && !PermissionAccess::can('teacher.grades.update')) {
            throw ValidationException::withMessages([
                'permission' => 'Você não tem permissão para salvar notas.',
            ]);
        }

        if ($publish && !PermissionAccess::can('teacher.grades.publish')) {
            throw ValidationException::withMessages([
                'permission' => 'Você não tem permissão para publicar notas.',
            ]);
        }

        $maxScore       = (float) ($assessment->max_score ?? 10);
        $term           = $this->resolveTerm($assessment);
        $assessmentType = $this->mapAssessmentType($assessment->assessment_type);
        $sequence       = 1;

        $summary = app(RecordTeacherGrades::class)->execute(
            teacher: $teacher,
            assessment: $assessment,
            students: $students,
            gradeRows: $this->gradeRows,
            maxScore: $maxScore,
            term: $term,
            assessmentType: $assessmentType,
            sequence: $sequence,
            publish: $publish,
            postedBy: auth()->id(),
        );

        $created = $summary['created'];
        $updated = $summary['updated'];

        $this->saveSummary = [
            'created'   => $created,
            'updated'   => $updated,
            'deleted'   => $summary['deleted'],
            'total'     => $created + $updated,
            'published' => $publish,
        ];

        $this->syncGradeRows();

        Notification::make()
            ->title($publish ? 'Notas publicadas' : 'Rascunho salvo')
            ->body(sprintf('%d registros salvos e %d removidos.', $created + $updated, $summary['deleted']))
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
        return app(CurrentTeacherService::class)->currentAssignments($teacher)
            ->filter(fn (TeacherAssignment $assignment) => $assignment->schoolClass?->status === ClassStatus::OPEN)
            ->values();
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
     *
     * @return array|null
     */
    private function resolveContext(?Teacher $teacher, Collection $assignments): ?array {
        if (!$teacher || $assignments->isEmpty() || !$this->selectedClassId || !$this->selectedSubjectId || !$this->selectedAssessmentId) {
            return null;
        }

        $assignment = $assignments->first(function (TeacherAssignment $item) {
            return (int) $item->class_id === (int) $this->selectedClassId
                && (int) $item->subject_id === (int) $this->selectedSubjectId;
        });

        if (!$assignment) {
            return null;
        }

        $assessment = Assessment::query()
            ->forTeacher($teacher->id)
            ->where('id', $this->selectedAssessmentId)
            ->where('class_id', $assignment->class_id)
            ->where('subject_id', $assignment->subject_id)
            ->where('school_year_id', SchoolYear::current()?->id)
            ->with(['schoolYear'])
            ->first();

        if (!$assessment || (int) $assignment->schoolClass?->school_year_id !== (int) $assessment->school_year_id) {
            return null;
        }

        return [
            'assessment' => $assessment,
            'class' => $assignment->schoolClass,
            'schoolYear' => $assessment->schoolYear,
        ];
    }

    /**
     * Monta os dados dos alunos usados nos lançamentos.
     *
     * @param Assessment $assessment
     *
     * @return Collection
     */
    private function buildStudents(Assessment $assessment): Collection {
        $enrollments = app(TeacherRosterService::class)->forAssessment($assessment);

        $existing = Grade::query()
            ->where('assessment_id', $assessment->id)
            ->get()
            ->keyBy('student_id');

        return $enrollments->map(function (Enrollment $enrollment) use ($existing, $assessment) {
            $grade   = $existing->get($enrollment->student_id);
            $student = $enrollment->student;

            $score = array_key_exists($enrollment->student_id, $this->gradeRows)
                ? ($this->gradeRows[$enrollment->student_id]['score'] ?? null)
                : $grade?->score;
            $comment = array_key_exists($enrollment->student_id, $this->gradeRows)
                ? ($this->gradeRows[$enrollment->student_id]['comment'] ?? null)
                : $grade?->comment;

            $this->gradeRows[$enrollment->student_id] = [
                'score'   => $score,
                'comment' => $comment,
            ];

            return [
                'enrollment_id'       => $enrollment->id,
                'student_id'          => $enrollment->student_id,
                'student_name'        => $student?->name ?? '—',
                'registration_number' => $student?->registration_number ?? '—',
                'roll_number'         => $enrollment->roll_number,
                'score'               => $score,
                'comment'             => $comment,
                'locked'              => (bool) $grade?->locked_at,
                'max_score'           => (float) ($assessment->max_score ?? 10),
            ];
        });
    }

    /**
     * Sincroniza as linhas de notas com os alunos carregados.
     *
     * @return void
     */
    private function syncGradeRows(): void {
        $teacher     = $this->currentTeacher();
        $assignments = $this->teacherAssignments($teacher);
        $context     = $this->resolveContext($teacher, $assignments);

        if (!$context) {
            $this->gradeRows = [];

            return;
        }

        $students = $this->buildStudents($context['assessment']);

        $this->gradeRows = $students->mapWithKeys(function (array $row) {
            return [
                $row['student_id'] => [
                    'score'   => $row['score'],
                    'comment' => $row['comment'],
                ],
            ];
        })->all();
    }

    /**
     * Retorna as turmas disponíveis para lançamento de notas.
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
            ->mapWithKeys(fn ($class) => [
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
     * Retorna as avaliações disponíveis para a turma e a disciplina selecionadas.
     *
     * @param Teacher|null $teacher
     * @param int|null $classId
     * @param int|null $subjectId
     *
     * @return array
     */
    private function assessmentOptions(?Teacher $teacher, ?int $classId, ?int $subjectId): array {
        $yearId = SchoolYear::current()?->id;
        if (!$teacher || !$yearId || !$classId || !$subjectId
            || !$this->teacherAssignments($teacher)->contains(fn (TeacherAssignment $assignment) =>
                (int) $assignment->class_id === (int) $classId
                && (int) $assignment->subject_id === (int) $subjectId
            )) {
            return [];
        }

        $query = Assessment::query()->forTeacher($teacher->id)
            ->where('school_year_id', $yearId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->orderByDesc('date');

        return $query
            ->get()
            ->mapWithKeys(fn (Assessment $assessment) => [
                $assessment->id => trim(($assessment->title ?? 'Avaliação') . ' • ' . ($assessment->date?->format('d/m/Y') ?? 'Sem data')),
            ])
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

    /**
     * Retorna o identificador da primeira avaliação disponível.
     *
     * @param Teacher|null $teacher
     * @param int|null $classId
     * @param int|null $subjectId
     *
     * @return int|null
     */
    private function firstAvailableAssessmentId(?Teacher $teacher, ?int $classId, ?int $subjectId): ?int {
        if (!$teacher) {
            return null;
        }

        $options = $this->assessmentOptions($teacher, $classId, $subjectId);

        return array_key_first($options);
    }

    /**
     * Resolve o período letivo correspondente à avaliação.
     *
     * @param Assessment $assessment
     *
     * @return string
     */
    private function resolveTerm(Assessment $assessment): string {
        $term = $assessment->schoolYear?->currentTerm();

        if (!$term) {
            return Term::B1->value;
        }

        return match ((int) $term->sequence) {
            1       => Term::B1->value,
            2       => Term::B2->value,
            3       => Term::B3->value,
            4       => Term::B4->value,
            default => Term::B1->value,
        };
    }

    /**
     * Converte o tipo da avaliação para o valor aceito pelo formulário.
     *
     * @param string|null $assessmentType
     *
     * @return string
     */
    private function mapAssessmentType(?string $assessmentType): string {
        return match ($assessmentType) {
            'prova'       => AssessmentType::TEST->value,
            'trabalho'    => AssessmentType::WORK->value,
            'atividade'   => AssessmentType::QUIZ->value,
            'seminario'   => AssessmentType::PARTICIPATION->value,
            'projeto'     => AssessmentType::PROJECT->value,
            'recuperacao' => AssessmentType::RECOVERY->value,
            default       => AssessmentType::TEST->value,
        };
    }

    /**
     * Retorna o resumo calculado para exibição.
     *
     * @param Collection $students
     *
     * @return array
     */
    private function buildSummary(Collection $students): array {
        $total  = $students->count();
        $filled = $students->filter(fn (array $row) => $row['score'] !== null && $row['score'] !== '')->count();

        return [
            'total'     => $total,
            'filled'    => $filled,
            'remaining' => max($total - $filled, 0),
        ];
    }

    /**
     * Retorna a estrutura vazia do resumo.
     *
     * @return array
     */
    private function emptySummary(): array {
        return [
            'total'     => 0,
            'filled'    => 0,
            'remaining' => 0,
        ];
    }
}
