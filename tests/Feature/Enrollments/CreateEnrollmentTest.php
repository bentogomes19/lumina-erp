<?php

namespace Tests\Feature\Enrollments;

use App\Models\Enrollment;
use App\Models\EnrollmentLog;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use App\Services\Enrollments\StudentEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use RuntimeException;
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
