<?php

namespace Tests\Feature\Enrollments;

use App\Enums\StudentOnboardingState;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use App\Services\Enrollments\StudentEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StudentOnboardingTest extends TestCase {

    use RefreshDatabase;

    /**
     * Verifica que o pré-cadastro cria somente o aluno.
     *
     * @return void
     */
    public function test_pre_registration_does_not_grant_access_or_create_enrollment(): void {
        $student = $this->service()->preRegister($this->studentData());

        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertNull($student->user_id);
        $this->assertSame(StudentOnboardingState::WITHOUT_USER, $this->service()->onboardingState($student));
    }

    /**
     * Verifica a detecção do mesmo aluno por CPF, registro, RG e e-mail.
     *
     * @return void
     */
    public function test_pre_registration_detects_existing_student_by_every_identity(): void {
        $student = $this->service()->preRegister(array_merge($this->studentData(), [
            'registration_number' => 'ALU-2026-000001',
        ]));
        $identities = [
            ['cpf' => '12345678901'],
            ['registration_number' => 'ALU-2026-000001'],
            ['rg' => 'MG123456'],
            ['email' => 'aluno@example.test'],
        ];

        foreach ($identities as $identity) {
            try {
                $this->service()->preRegister(array_merge(['name' => 'Outro aluno'], $identity));
                $this->fail('O identificador duplicado deveria localizar o aluno existente.');
            } catch (ValidationException $exception) {
                $this->assertStringContainsString($student->registration_number, $exception->errors()['name'][0]);
            }
        }

        $this->assertDatabaseCount('students', 1);
    }

    /**
     * Verifica que usuário e convite podem ser concedidos em operações separadas.
     *
     * @return void
     */
    public function test_access_user_and_invitation_are_separate_operations(): void {
        Notification::fake();
        $this->createStudentRole();
        $student = $this->service()->preRegister($this->studentData());

        $user = $this->service()->createAccess($student);

        $this->assertTrue($user->hasRole('student'));
        $this->assertFalse(app(\App\Services\Auth\FirstAccessInvitationService::class)->hasActiveToken($user));
        $this->assertSame(
            StudentOnboardingState::WITHOUT_ENROLLMENT,
            $this->service()->onboardingState($student->refresh()),
        );

        $url = $this->service()->inviteAccess($student->refresh());

        $this->assertNotSame('', $url);
        $this->assertTrue(app(\App\Services\Auth\FirstAccessInvitationService::class)->hasActiveToken($user));
    }

    /**
     * Verifica que a matrícula pode ser concluída sem criar acesso.
     *
     * @return void
     */
    public function test_complete_enrollment_can_keep_student_without_user(): void {
        $schoolClass = $this->schoolClass();
        $data        = $this->enrollmentData($schoolClass);
        $data['access_action'] = 'none';

        $result  = $this->service()->create($data);
        $student = $result->enrollment->student()->firstOrFail();

        $this->assertFalse($result->userCreated);
        $this->assertFalse($result->invitationSent);
        $this->assertNull($student->user_id);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('enrollments', 1);
    }

    /**
     * Verifica que a matrícula pode criar o usuário sem enviar convite.
     *
     * @return void
     */
    public function test_complete_enrollment_can_create_user_without_invitation(): void {
        $this->createStudentRole();
        $data                  = $this->enrollmentData($this->schoolClass());
        $data['access_action'] = 'create';

        $result = $this->service()->create($data);

        $this->assertTrue($result->userCreated);
        $this->assertFalse($result->invitationSent);
        $this->assertNull($result->invitationUrl);
        $this->assertSame(
            StudentOnboardingState::COMPLETE,
            $this->service()->onboardingState($result->enrollment->student()->firstOrFail()),
        );
    }

    /**
     * Verifica a identificação de um vínculo de usuário com papel incompatível.
     *
     * @return void
     */
    public function test_student_with_incompatible_user_is_inconsistent(): void {
        Role::create([
            'name'       => 'teacher',
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create();
        $user->assignRole('teacher');
        $student = Student::create(array_merge($this->studentData(), [
            'user_id' => $user->id,
        ]));

        $this->assertSame(
            StudentOnboardingState::INCONSISTENT,
            $this->service()->onboardingState($student),
        );
    }

    /**
     * Retorna o serviço exercitado pelos testes.
     *
     * @return StudentEnrollmentService
     */
    private function service(): StudentEnrollmentService {
        return app(StudentEnrollmentService::class);
    }

    /**
     * Retorna os dados válidos de um pré-cadastro.
     *
     * @return array<string, mixed>
     */
    private function studentData(): array {
        return [
            'name'       => 'Aluno Onboarding',
            'cpf'        => '123.456.789-01',
            'rg'         => 'MG-12.345-6',
            'email'      => 'aluno@example.test',
            'birth_date' => '2014-05-10',
            'status'     => 'active',
        ];
    }

    /**
     * Retorna os dados válidos de uma matrícula completa.
     *
     * @param SchoolClass $schoolClass
     *
     * @return array<string, mixed>
     */
    private function enrollmentData(SchoolClass $schoolClass): array {
        return [
            'submission_token'     => (string) Str::uuid(),
            'student_source'       => 'new',
            'student_name'         => 'Aluno Matriculado',
            'student_birth_date'   => '2014-05-10',
            'student_email'        => 'matriculado@example.test',
            'student_phone_number' => '(11) 99999-0000',
            'class_id'             => $schoolClass->id,
            'enrollment_date'      => '2026-02-02',
            'status'               => 'Ativa',
        ];
    }

    /**
     * Cria a estrutura acadêmica mínima usada nas matrículas.
     *
     * @return SchoolClass
     */
    private function schoolClass(): SchoolClass {
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
     * Cria o papel necessário para o acesso do aluno.
     *
     * @return Role
     */
    private function createStudentRole(): Role {
        return Role::create([
            'name'       => 'student',
            'guard_name' => 'web',
        ]);
    }
}
