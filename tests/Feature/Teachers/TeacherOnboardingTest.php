<?php

namespace Tests\Feature\Teachers;

use App\Enums\TeacherAccessAction;
use App\Enums\TeacherOnboardingState;
use App\Enums\TeacherStatus;
use App\Models\GradeLevel;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\User;
use App\Services\Teachers\TeacherOnboardingService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TeacherOnboardingTest extends TestCase {

    use RefreshDatabase;

    /**
     * Garante que a opção sem acesso crie somente o cadastro docente.
     *
     * @return void
     */
    public function test_onboarding_without_access_does_not_create_user(): void {
        $result = $this->service()->create($this->teacherData());

        $this->assertFalse($result->userCreated);
        $this->assertNull($result->teacher->user_id);
        $this->assertDatabaseCount('users', 0);
        $this->assertSame(
            TeacherOnboardingState::WITHOUT_USER,
            $this->service()->onboardingState($result->teacher),
        );
    }

    /**
     * Garante que um acesso sem alocações seja identificado para continuidade administrativa.
     *
     * @return void
     */
    public function test_onboarding_with_access_and_without_assignment_has_clear_state(): void {
        $this->createTeacherRole();
        $data                  = $this->teacherData();
        $data['access_action'] = TeacherAccessAction::CREATE->value;
        $result                = $this->service()->create($data);

        $this->assertTrue($result->userCreated);
        $this->assertNotNull($result->teacher->user_id);
        $this->assertTrue($result->teacher->user->hasRole('teacher'));
        $this->assertSame(
            TeacherOnboardingState::WITHOUT_ASSIGNMENT,
            $this->service()->onboardingState($result->teacher),
        );
    }

    /**
     * Verifica que o acesso docente é herdado pelo papel sem cópia direta.
     *
     * @return void
     */
    public function test_teacher_access_uses_role_permissions_without_direct_copy(): void {
        $permission = Permission::create([
            'name'       => 'teacher.dashboard.view',
            'guard_name' => 'web',
        ]);
        $role = $this->createTeacherRole();
        $role->givePermissionTo($permission);
        $data                  = $this->teacherData();
        $data['access_action'] = TeacherAccessAction::CREATE->value;

        $result = $this->service()->create($data);
        $user   = $result->teacher->user()->firstOrFail();

        $this->assertTrue($user->hasRole('teacher'));
        $this->assertTrue($user->can('teacher.dashboard.view'));
        $this->assertDatabaseMissing('model_has_permissions', [
            'permission_id' => $permission->id,
            'model_type'    => User::class,
            'model_id'      => $user->id,
        ]);

        $role->revokePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertFalse($user->fresh()->can('teacher.dashboard.view'));
    }

    /**
     * Garante que o fluxo completo crie acesso e alocação prontos para uso.
     *
     * @return void
     */
    public function test_complete_onboarding_creates_user_and_assignment(): void {
        $this->createTeacherRole();
        [$schoolClass, $subject] = $this->academicStructure();
        $data                    = $this->teacherData();
        $data['access_action']   = TeacherAccessAction::CREATE->value;
        $data['assignments']     = [[
            'class_id'   => $schoolClass->id,
            'subject_id' => $subject->id,
        ]];

        $result = $this->service()->create($data);

        $this->assertTrue($result->userCreated);
        $this->assertSame(1, $result->assignmentsCreated);
        $this->assertDatabaseHas('teacher_assignments', [
            'teacher_id' => $result->teacher->id,
            'class_id'   => $schoolClass->id,
            'subject_id' => $subject->id,
        ]);
        $this->assertSame(
            TeacherOnboardingState::READY_FOR_ACCESS,
            $this->service()->onboardingState($result->teacher),
        );
        $this->assertTrue(
            $result->teacher->user->canAccessPanel(Filament::getPanel('professor')),
        );
    }

    /**
     * Garante que uma submissão repetida não duplique professor, usuário ou alocação.
     *
     * @return void
     */
    public function test_reprocessing_same_onboarding_is_idempotent(): void {
        $this->createTeacherRole();
        [$schoolClass, $subject] = $this->academicStructure();
        $data                    = $this->teacherData();
        $data['access_action']   = TeacherAccessAction::CREATE->value;
        $data['assignments']     = [[
            'class_id'   => $schoolClass->id,
            'subject_id' => $subject->id,
        ]];

        $first  = $this->service()->create($data);
        $replay = $this->service()->create($data);

        $this->assertSame($first->teacher->id, $replay->teacher->id);
        $this->assertTrue($replay->replayed);
        $this->assertDatabaseCount('teachers', 1);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('teacher_assignments', 1);
    }

    /**
     * Garante a detecção normalizada de CPF, matrícula funcional e e-mail duplicados.
     *
     * @return void
     */
    public function test_onboarding_rejects_duplicate_teacher_identity(): void {
        $this->service()->create($this->teacherData());
        $attempts = [
            array_merge($this->teacherData('DOC-002'), [
                'cpf'   => '52998224725',
                'email' => 'outro-cpf@example.test',
            ]),
            array_merge($this->teacherData('doc-001'), [
                'cpf'   => '111.444.777-35',
                'email' => 'outra-matricula@example.test',
            ]),
            array_merge($this->teacherData('DOC-003'), [
                'cpf'   => '123.456.789-09',
                'email' => ' PROFESSOR@EXAMPLE.TEST ',
            ]),
        ];

        foreach ($attempts as $attempt) {
            try {
                $this->service()->create($attempt);
                $this->fail('O onboarding deveria rejeitar a identidade docente duplicada.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('name', $exception->errors());
            }
        }

        $this->assertDatabaseCount('teachers', 1);
    }

    /**
     * Garante que repetir a mesma alocação não duplique o vínculo e que conflitos sejam rejeitados.
     *
     * @return void
     */
    public function test_assignment_shortcuts_are_idempotent_and_reject_conflicts(): void {
        [$schoolClass, $subject] = $this->academicStructure();
        $teacher                = $this->service()->create($this->teacherData())->teacher;
        $assignmentData         = [
            'class_id'   => $schoolClass->id,
            'subject_id' => $subject->id,
        ];

        $first  = $this->service()->createAssignment($teacher, $assignmentData);
        $replay = $this->service()->createAssignment($teacher, $assignmentData);

        $this->assertSame($first->id, $replay->id);
        $this->assertDatabaseCount('teacher_assignments', 1);

        $otherTeacher = $this->service()->create($this->teacherData('DOC-002'))->teacher;

        try {
            $this->service()->createAssignment($otherTeacher, $assignmentData);
            $this->fail('A alocação conflitante deveria ser rejeitada.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('subject_id', $exception->errors());
        }

        $this->assertDatabaseCount('teacher_assignments', 1);
    }

    /**
     * Garante que disciplina fora da matriz curricular seja rejeitada sem exceção.
     *
     * @return void
     */
    public function test_assignment_rejects_subject_outside_grade_level_curriculum(): void {
        [$schoolClass] = $this->academicStructure();
        $teacher       = $this->service()->create($this->teacherData())->teacher;
        $subject       = $this->subject('ART-001', 'Arte');

        try {
            $this->service()->createAssignment($teacher, [
                'class_id'   => $schoolClass->id,
                'subject_id' => $subject->id,
            ]);
            $this->fail('A disciplina fora da matriz curricular deveria ser rejeitada.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('subject_id', $exception->errors());
        }

        $this->assertDatabaseCount('teacher_assignments', 0);
    }

    /**
     * Garante que exceção curricular exija permissão administrativa.
     *
     * @return void
     */
    public function test_curriculum_exception_requires_permission(): void {
        [$schoolClass] = $this->academicStructure();
        $teacher       = $this->service()->create($this->teacherData())->teacher;
        $subject       = $this->subject('ART-001', 'Arte');
        $this->actingAs(User::factory()->create());

        try {
            $this->service()->createAssignment($teacher, [
                'class_id'                           => $schoolClass->id,
                'subject_id'                         => $subject->id,
                'curriculum_exception'               => true,
                'curriculum_exception_justification' => 'Projeto interdisciplinar autorizado.',
            ]);
            $this->fail('A exceção curricular sem permissão deveria ser rejeitada.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('curriculum_exception', $exception->errors());
        }

        $this->assertDatabaseCount('teacher_assignments', 0);
    }

    /**
     * Garante que exceção autorizada seja registrada com justificativa.
     *
     * @return void
     */
    public function test_authorized_curriculum_exception_is_registered_with_justification(): void {
        [$schoolClass] = $this->academicStructure();
        $teacher       = $this->service()->create($this->teacherData())->teacher;
        $subject       = $this->subject('ART-001', 'Arte');
        $this->actingAsUserWithCurriculumExceptionPermission();

        $assignment = $this->service()->createAssignment($teacher, [
            'class_id'                           => $schoolClass->id,
            'subject_id'                         => $subject->id,
            'curriculum_exception'               => true,
            'curriculum_exception_justification' => 'Projeto interdisciplinar autorizado.',
        ]);

        $this->assertTrue($assignment->curriculum_exception);
        $this->assertSame('Projeto interdisciplinar autorizado.', $assignment->curriculum_exception_justification);
        $this->assertDatabaseHas('class_subjects', [
            'class_id'   => $schoolClass->id,
            'subject_id' => $subject->id,
        ]);
    }

    /**
     * Garante que exceção curricular autorizada continue exigindo justificativa.
     *
     * @return void
     */
    public function test_authorized_curriculum_exception_requires_justification(): void {
        [$schoolClass] = $this->academicStructure();
        $teacher       = $this->service()->create($this->teacherData())->teacher;
        $subject       = $this->subject('ART-001', 'Arte');
        $this->actingAsUserWithCurriculumExceptionPermission();

        try {
            $this->service()->createAssignment($teacher, [
                'class_id'             => $schoolClass->id,
                'subject_id'           => $subject->id,
                'curriculum_exception' => true,
            ]);
            $this->fail('A exceção curricular sem justificativa deveria ser rejeitada.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('curriculum_exception_justification', $exception->errors());
        }
    }

    /**
     * Garante que remover a última alocação não remova disciplina da matriz da turma.
     *
     * @return void
     */
    public function test_deleting_last_assignment_keeps_curricular_class_subject(): void {
        [$schoolClass, $subject] = $this->academicStructure();
        $teacher                = $this->service()->create($this->teacherData())->teacher;
        $assignment             = $this->service()->createAssignment($teacher, [
            'class_id'   => $schoolClass->id,
            'subject_id' => $subject->id,
        ]);

        $assignment->delete();

        $this->assertDatabaseHas('class_subjects', [
            'class_id'   => $schoolClass->id,
            'subject_id' => $subject->id,
        ]);
    }

    /**
     * Garante que remover a última exceção operacional remova o vínculo direto da turma.
     *
     * @return void
     */
    public function test_deleting_last_exception_assignment_removes_non_curricular_class_subject(): void {
        [$schoolClass] = $this->academicStructure();
        $teacher       = $this->service()->create($this->teacherData())->teacher;
        $subject       = $this->subject('ART-001', 'Arte');
        $this->actingAsUserWithCurriculumExceptionPermission();
        $assignment = $this->service()->createAssignment($teacher, [
            'class_id'                           => $schoolClass->id,
            'subject_id'                         => $subject->id,
            'curriculum_exception'               => true,
            'curriculum_exception_justification' => 'Projeto interdisciplinar autorizado.',
        ]);

        $assignment->delete();

        $this->assertDatabaseMissing('class_subjects', [
            'class_id'   => $schoolClass->id,
            'subject_id' => $subject->id,
        ]);
    }

    /**
     * Garante que professores inativos, afastados ou desligados não acessem nem recebam convite.
     *
     * @return void
     */
    public function test_non_operational_teacher_cannot_access_or_receive_invitation(): void {
        $this->createTeacherRole();
        [$schoolClass] = $this->academicStructure();
        $statuses      = [
            TeacherStatus::INACTIVE,
            TeacherStatus::SABBATICAL,
            TeacherStatus::TERMINATED,
        ];

        foreach ($statuses as $index => $status) {
            $subject = Subject::create([
                'code'     => 'STATUS-'.($index + 1),
                'name'     => 'Disciplina '.($index + 1),
                'category' => 'matematica',
                'status'   => 'active',
            ]);
            $schoolClass->gradeLevel->subjects()->attach($subject->id);
            $data                  = $this->teacherData('DOC-'.($index + 10));
            $data['status']        = $status->value;
            $data['access_action'] = TeacherAccessAction::CREATE->value;
            $data['assignments']   = [[
                'class_id'   => $schoolClass->id,
                'subject_id' => $subject->id,
            ]];
            $result                = $this->service()->create($data);

            $this->assertFalse(
                $result->teacher->user->canAccessPanel(Filament::getPanel('professor')),
            );
            $this->assertSame(
                TeacherOnboardingState::ACCESS_BLOCKED,
                $this->service()->onboardingState($result->teacher),
            );

            try {
                $this->service()->inviteAccess($result->teacher);
                $this->fail('Professor sem situação operacional não deveria receber convite.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('email', $exception->errors());
            }
        }
    }

    /**
     * Retorna uma nova instância do serviço de onboarding docente.
     *
     * @return TeacherOnboardingService
     */
    private function service(): TeacherOnboardingService {
        return app(TeacherOnboardingService::class);
    }

    /**
     * Retorna dados válidos e únicos para cadastrar um professor.
     *
     * @param string $employeeNumber
     *
     * @return array<string, mixed>
     */
    private function teacherData(string $employeeNumber = 'DOC-001'): array {
        $suffix = strtolower(str_replace('DOC-', '', $employeeNumber));

        return [
            'onboarding_token' => (string) Str::uuid(),
            'name'             => "Professor {$suffix}",
            'employee_number'  => $employeeNumber,
            'cpf'              => $employeeNumber === 'DOC-001' ? '529.982.247-25' : null,
            'email'            => "professor-{$suffix}@example.test",
            'status'           => TeacherStatus::ACTIVE->value,
            'access_action'    => TeacherAccessAction::NONE->value,
        ];
    }

    /**
     * Cria turma e disciplina para os cenários de alocação.
     *
     * @return array{0: SchoolClass, 1: Subject}
     */
    private function academicStructure(): array {
        $schoolYear = SchoolYear::create([
            'year'      => 2026,
            'starts_at' => '2026-02-02',
            'ends_at'   => '2026-12-18',
            'is_active' => true,
            'status'    => 'ativo',
        ]);
        $gradeLevel = GradeLevel::create([
            'name'          => '1º Ano Fundamental',
            'stage'         => 'fundamental_i',
            'display_order' => 1,
        ]);
        $schoolClass = SchoolClass::create([
            'uuid'           => (string) Str::uuid(),
            'name'           => 'Turma A',
            'grade_level_id' => $gradeLevel->id,
            'school_year_id' => $schoolYear->id,
            'shift'          => 'morning',
            'type'           => 'regular',
            'capacity'       => 30,
            'status'         => 'open',
        ]);
        $subject = Subject::create([
            'code'     => 'MAT-001',
            'name'     => 'Matemática',
            'category' => 'matematica',
            'status'   => 'active',
        ]);
        $gradeLevel->subjects()->attach($subject->id);

        return [$schoolClass, $subject];
    }

    /**
     * Cria uma disciplina ativa para uso nos cenários acadêmicos.
     *
     * @param string $code
     * @param string $name
     *
     * @return Subject
     */
    private function subject(string $code, string $name): Subject {
        return Subject::create([
            'code'     => $code,
            'name'     => $name,
            'category' => 'matematica',
            'status'   => 'active',
        ]);
    }

    /**
     * Autentica um usuário com permissão para exceção curricular.
     *
     * @return User
     */
    private function actingAsUserWithCurriculumExceptionPermission(): User {
        $permission = Permission::create([
            'name'       => 'admin.teachers.assignments.curriculum_exception',
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create();
        $user->givePermissionTo($permission);
        $this->actingAs($user);

        return $user;
    }

    /**
     * Cria o papel necessário para o acesso docente.
     *
     * @return Role
     */
    private function createTeacherRole(): Role {
        return Role::create([
            'name'       => 'teacher',
            'guard_name' => 'web',
        ]);
    }

}
