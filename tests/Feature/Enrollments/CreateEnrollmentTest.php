<?php

namespace Tests\Feature\Enrollments;

use App\Models\Enrollment;
use App\Models\EnrollmentLog;
use App\Models\GradeLevel;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use App\Services\Enrollments\StudentEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CreateEnrollmentTest extends TestCase {

    use RefreshDatabase;

    /**
     * Verifica a criação completa do aluno e de todos os vínculos de onboarding.
     *
     * @return void
     */
    public function test_complete_flow_creates_all_expected_records(): void {
        $schoolClass = $this->schoolClass();
        $this->createStudentRole();

        $result = $this->service()->create($this->newStudentData($schoolClass));

        $student    = $result->enrollment->student()->firstOrFail();
        $user       = $student->user()->firstOrFail();
        $enrollment = $result->enrollment->fresh();

        $this->assertTrue($result->userCreated);
        $this->assertFalse($result->replayed);
        $this->assertNotNull($result->invitationUrl);
        $this->assertTrue($user->hasRole('student'));
        $this->assertSame(1, $enrollment->roll_number);
        $this->assertNotNull($enrollment->registration_number);
        $this->assertDatabaseHas('enrollment_logs', [
            'enrollment_id' => $enrollment->id,
            'acao'          => 'criacao',
            'status_novo'   => 'Ativa',
        ]);
    }

    /**
     * Verifica que o acesso do aluno é herdado pelo papel e revogado pelo papel.
     *
     * @return void
     */
    public function test_student_access_uses_role_permissions_without_direct_copy(): void {
        $schoolClass = $this->schoolClass();
        $permission  = Permission::create([
            'name'       => 'student.dashboard.view',
            'guard_name' => 'web',
        ]);
        $role = $this->createStudentRole();
        $role->givePermissionTo($permission);

        $result = $this->service()->create($this->newStudentData($schoolClass));
        $user   = $result->enrollment->student()->firstOrFail()->user()->firstOrFail();

        $this->assertTrue($user->hasRole('student'));
        $this->assertTrue($user->can('student.dashboard.view'));
        $this->assertDatabaseMissing('model_has_permissions', [
            'permission_id' => $permission->id,
            'model_type'    => User::class,
            'model_id'      => $user->id,
        ]);

        $role->revokePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertFalse($user->fresh()->can('student.dashboard.view'));
    }

    /**
     * Verifica o rollback do aluno quando a criação da matrícula falha.
     *
     * @return void
     */
    public function test_enrollment_failure_rolls_back_new_student(): void {
        $schoolClass = $this->schoolClass();
        $this->createStudentRole();
        Event::listen('eloquent.creating: ' . Enrollment::class, function (): void {
            throw new RuntimeException('Falha simulada na matrícula.');
        });

        $this->assertAtomicRollback(function () use ($schoolClass): void {
            $this->service()->create($this->newStudentData($schoolClass));
        });
    }

    /**
     * Verifica o rollback de aluno, matrícula e log quando a criação do usuário falha.
     *
     * @return void
     */
    public function test_user_failure_rolls_back_student_enrollment_and_log(): void {
        $schoolClass = $this->schoolClass();
        $this->createStudentRole();
        Event::listen('eloquent.creating: ' . User::class, function (): void {
            throw new RuntimeException('Falha simulada no usuário.');
        });

        $this->assertAtomicRollback(function () use ($schoolClass): void {
            $this->service()->create($this->newStudentData($schoolClass));
        });
    }

    /**
     * Verifica o rollback integral quando o papel obrigatório não está configurado.
     *
     * @return void
     */
    public function test_role_failure_rolls_back_entire_onboarding(): void {
        $schoolClass = $this->schoolClass();

        $this->assertAtomicRollback(function () use ($schoolClass): void {
            $this->service()->create($this->newStudentData($schoolClass));
        });
    }

    /**
     * Verifica o rollback integral quando o registro de auditoria falha.
     *
     * @return void
     */
    public function test_audit_failure_rolls_back_entire_onboarding(): void {
        $schoolClass = $this->schoolClass();
        $this->createStudentRole();
        Event::listen('eloquent.creating: ' . EnrollmentLog::class, function (): void {
            throw new RuntimeException('Falha simulada na auditoria.');
        });

        $this->assertAtomicRollback(function () use ($schoolClass): void {
            $this->service()->create($this->newStudentData($schoolClass));
        });
    }

    /**
     * Verifica que a repetição da mesma submissão retorna a matrícula já confirmada.
     *
     * @return void
     */
    public function test_repeated_submission_does_not_duplicate_onboarding(): void {
        $schoolClass = $this->schoolClass();
        $this->createStudentRole();
        $data = $this->newStudentData($schoolClass);

        $first  = $this->service()->create($data);
        $second = $this->service()->create($data);

        $this->assertSame($first->enrollment->id, $second->enrollment->id);
        $this->assertTrue($second->replayed);
        $this->assertFalse($second->userCreated);
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('enrollments', 1);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('enrollment_logs', 1);
    }

    /**
     * Verifica que a criação do acesso exige um e-mail válido para o aluno.
     *
     * @return void
     */
    public function test_student_access_requires_a_valid_email(): void {
        $schoolClass = $this->schoolClass();
        $this->createStudentRole();
        $data                  = $this->newStudentData($schoolClass);
        $data['student_email'] = null;

        try {
            $this->service()->create($data);
            $this->fail('A matrícula sem e-mail de acesso deveria ser rejeitada.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Informe um e-mail válido para criar o acesso do aluno.',
                $exception->errors()['student_email'][0],
            );
        }

        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Verifica que um e-mail já vinculado gera uma mensagem objetiva e não deixa registros parciais.
     *
     * @return void
     */
    public function test_student_access_rejects_an_email_already_in_use(): void {
        $schoolClass = $this->schoolClass();
        $this->createStudentRole();
        User::factory()->create(['email' => 'aluno.silva@example.test']);

        try {
            $this->service()->create($this->newStudentData($schoolClass));
            $this->fail('A matrícula com e-mail duplicado deveria ser rejeitada.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Este e-mail já está vinculado a outro usuário.',
                $exception->errors()['student_email'][0],
            );
        }

        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseCount('users', 1);
    }

    /**
     * Verifica que o Wizard não cria registros parciais quando a turma está cheia.
     *
     * @return void
     */
    public function test_wizard_rejects_full_class_without_creating_student(): void {
        $schoolClass = $this->schoolClass();
        $schoolClass->update(['capacity' => 1]);
        $occupant = Student::create([
            'uuid'                => (string) Str::uuid(),
            'registration_number' => 'ALU-OCUPANTE',
            'name'                => 'Aluno Ocupante',
            'status'              => 'active',
        ]);
        Enrollment::create([
            'student_id'     => $occupant->id,
            'class_id'       => $schoolClass->id,
            'school_year_id' => $schoolClass->school_year_id,
            'status'         => 'Ativa',
        ]);
        $this->createStudentRole();

        try {
            $this->service()->create($this->newStudentData($schoolClass));
            $this->fail('O Wizard deveria rejeitar uma turma cheia.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'A turma Turma A atingiu a capacidade de 1 vaga. Ocupação atual: 1.',
                $exception->errors()['class_id'][0],
            );
        }

        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('enrollments', 1);
        $this->assertDatabaseMissing('students', ['name' => 'Aluno da Silva']);
    }

    /**
     * Retorna uma instância do serviço exercitado pelos testes.
     *
     * @return StudentEnrollmentService
     */
    private function service(): StudentEnrollmentService {
        return app(StudentEnrollmentService::class);
    }

    /**
     * Cria a estrutura acadêmica mínima para receber uma matrícula.
     *
     * @return SchoolClass
     */
    private function schoolClass(): SchoolClass {
        $schoolYear = SchoolYear::create([
            'year'       => 2026,
            'starts_at'  => '2026-02-02',
            'ends_at'    => '2026-12-18',
            'is_active'  => true,
            'status'     => 'ativo',
        ]);
        $gradeLevel = GradeLevel::create([
            'name'          => '1º Ano Fundamental',
            'stage'         => 'fundamental_i',
            'display_order' => 1,
        ]);

        return SchoolClass::create([
            'uuid'           => (string) Str::uuid(),
            'name'           => 'Turma A',
            'grade_level_id' => $gradeLevel->id,
            'school_year_id' => $schoolYear->id,
            'shift'          => 'morning',
            'type'           => 'regular',
            'capacity'       => 30,
            'status'         => 'open',
        ]);
    }

    /**
     * Cria o papel necessário para o onboarding do aluno.
     *
     * @return Role
     */
    private function createStudentRole(): Role {
        return Role::create([
            'name'       => 'student',
            'guard_name' => 'web',
        ]);
    }

    /**
     * Retorna os dados válidos de uma submissão para novo aluno.
     *
     * @param SchoolClass $schoolClass
     *
     * @return array<string, mixed>
     */
    private function newStudentData(SchoolClass $schoolClass): array {
        return [
            'submission_token'    => (string) Str::uuid(),
            'student_source'      => 'new',
            'student_name'        => 'Aluno da Silva',
            'student_birth_date'  => '2014-05-10',
            'student_gender'      => 'M',
            'student_email'       => 'aluno.silva@example.test',
            'student_phone_number' => '(11) 99999-0000',
            'class_id'             => $schoolClass->id,
            'enrollment_date'      => '2026-02-02',
            'status'               => 'Ativa',
            'access_action'        => 'create_and_invite',
        ];
    }

    /**
     * Executa uma falha simulada e confirma que nenhum registro parcial permaneceu.
     *
     * @param callable(): void $operation
     *
     * @return void
     */
    private function assertAtomicRollback(callable $operation): void {
        try {
            $operation();
            $this->fail('A operação deveria ter falhado.');
        } catch (RuntimeException $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }

        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('enrollment_logs', 0);
    }
}
