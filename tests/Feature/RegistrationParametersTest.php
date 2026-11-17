<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\SystemParameter;
use App\Models\Teacher;
use App\Services\Enrollments\StudentEnrollmentService;
use App\Support\EnrollmentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RegistrationParametersTest extends TestCase {

    use RefreshDatabase;

    /**
     * Fixa a data para que {year} e a idade dos alunos sejam previsíveis.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        $this->travelTo('2026-10-14 12:00:00');
    }

    /**
     * O número do aluno segue a máscara e a sequência do ano.
     *
     * @return void
     */
    public function test_student_number_follows_format_with_sequence(): void {
        $this->parameter('student.number_format', 'select', 'ALU-{year}-{sequence}');

        $this->assertSame('ALU-2026-000001', $this->student()->registration_number);
        $this->assertSame('ALU-2026-000002', $this->student()->registration_number);
    }

    /**
     * Outra máscara cadastrada é respeitada.
     *
     * @return void
     */
    public function test_student_number_uses_alternative_format(): void {
        $this->parameter('student.number_format', 'select', '{year}-{sequence}');

        $this->assertSame('2026-000001', $this->student()->registration_number);
    }

    /**
     * O número da matrícula usa a máscara com o ano letivo e o ID.
     *
     * @return void
     */
    public function test_enrollment_number_follows_format(): void {
        $this->parameter('enrollment.number_format', 'select', 'MAT-{year}-{sequence}');

        $enrollment = $this->enrollment();

        $this->assertSame('MAT-2026-' . str_pad((string) $enrollment->id, 6, '0', STR_PAD_LEFT), $enrollment->registration_number);
    }

    /**
     * Sem parâmetro, a matrícula mantém o formato anterior (ano + ID).
     *
     * @return void
     */
    public function test_enrollment_number_keeps_legacy_format_without_parameter(): void {
        $enrollment = $this->enrollment();

        $this->assertSame('2026' . str_pad((string) $enrollment->id, 6, '0', STR_PAD_LEFT), $enrollment->registration_number);
    }

    /**
     * A matrícula funcional é gerada quando não informada e preservada quando digitada.
     *
     * @return void
     */
    public function test_teacher_employee_number_is_generated_when_blank(): void {
        $this->parameter('teacher.number_format', 'select', 'PROF-{sequence}');

        $first  = Teacher::factory()->create(['employee_number' => null]);
        $second = Teacher::factory()->create(['employee_number' => null]);
        $typed  = Teacher::factory()->create(['employee_number' => 'MANUAL-01']);

        $this->assertSame('PROF-0001', $first->employee_number);
        $this->assertSame('PROF-0002', $second->employee_number);
        $this->assertSame('MANUAL-01', $typed->employee_number);
    }

    /**
     * Sem parâmetro, a matrícula funcional não é gerada (continua obrigatória no cadastro).
     *
     * @return void
     */
    public function test_teacher_employee_number_is_not_generated_without_parameter(): void {
        $this->assertNull(Teacher::generateEmployeeNumber());
    }

    /**
     * Responsável só é exigido para menor de idade e com o parâmetro desligado.
     *
     * @return void
     */
    public function test_guardian_requirement_follows_parameter_and_age(): void {
        $this->assertFalse(EnrollmentSettings::requiresGuardian('2016-05-10'), 'Sem parâmetro mantém o comportamento anterior.');

        $this->parameter('enrollment.allow_without_guardian', 'boolean', '0');

        $this->assertTrue(EnrollmentSettings::requiresGuardian('2016-05-10'));
        $this->assertFalse(EnrollmentSettings::requiresGuardian('2000-05-10'));
        $this->assertFalse(EnrollmentSettings::requiresGuardian(null));

        SystemParameter::where('key', 'enrollment.allow_without_guardian')->update(['value' => '1']);

        $this->assertFalse(EnrollmentSettings::requiresGuardian('2016-05-10'));
    }

    /**
     * A matrícula de aluno menor sem responsável é bloqueada quando o parâmetro não permite.
     *
     * @return void
     */
    public function test_minor_without_guardian_cannot_be_enrolled(): void {
        $this->parameter('enrollment.allow_without_guardian', 'boolean', '0');
        $student = $this->student(['birth_date' => '2016-05-10', 'guardian_main' => null]);

        try {
            app(StudentEnrollmentService::class)->enrollExistingStudent([
                'class_id'   => $this->schoolClass()->id,
                'student_id' => $student->id,
                'status'     => EnrollmentStatus::ACTIVE,
            ]);
            $this->fail('A matrícula de menor sem responsável deveria ser bloqueada.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('student_id', $exception->errors());
        }

        $this->assertDatabaseCount('enrollments', 0);
    }

    /**
     * Com responsável informado, o aluno menor é matriculado normalmente.
     *
     * @return void
     */
    public function test_minor_with_guardian_is_enrolled(): void {
        $this->parameter('enrollment.allow_without_guardian', 'boolean', '0');
        $student = $this->student(['birth_date' => '2016-05-10', 'guardian_main' => 'Maria Responsável']);

        app(StudentEnrollmentService::class)->enrollExistingStudent([
            'class_id'   => $this->schoolClass()->id,
            'student_id' => $student->id,
            'status'     => EnrollmentStatus::ACTIVE,
        ]);

        $this->assertDatabaseCount('enrollments', 1);
    }

    /**
     * Cria um aluno mínimo sem número informado.
     *
     * @param array<string, mixed> $attributes
     *
     * @return Student
     */
    private function student(array $attributes = []): Student {
        return Student::create(array_merge([
            'uuid'   => (string) Str::uuid(),
            'name'   => 'Aluno de Teste',
            'status' => 'active',
        ], $attributes));
    }

    /**
     * Cria uma matrícula em uma turma do ano letivo de 2026.
     *
     * @return Enrollment
     */
    private function enrollment(): Enrollment {
        return Enrollment::factory()
            ->create(['student_id' => $this->student()->id, 'class_id' => $this->schoolClass()->id])
            ->refresh();
    }

    /**
     * Cria uma turma aberta no ano letivo ativo de 2026.
     *
     * @return SchoolClass
     */
    private function schoolClass(): SchoolClass {
        $schoolYear = SchoolYear::query()->firstOrCreate(['year' => 2026], [
            'starts_at' => '2026-02-01',
            'ends_at'   => '2026-12-20',
            'is_active' => true,
            'status'    => 'ativo',
        ]);
        $gradeLevel = GradeLevel::query()->firstOrCreate(['name' => '5º Ano'], [
            'stage'         => 'fundamental_i',
            'display_order' => 5,
        ]);

        return SchoolClass::create([
            'uuid'           => (string) Str::uuid(),
            'code'           => 'TESTE-' . Str::upper(Str::random(6)),
            'name'           => 'Turma 2026',
            'grade_level_id' => $gradeLevel->id,
            'school_year_id' => $schoolYear->id,
            'shift'          => 'morning',
            'type'           => 'regular',
            'capacity'       => 35,
            'status'         => 'open',
        ]);
    }

    /**
     * Cadastra um parâmetro de Matrículas e cadastros.
     *
     * @param string $key
     * @param string $type
     * @param string $value
     *
     * @return void
     */
    private function parameter(string $key, string $type, string $value): void {
        SystemParameter::create([
            'key'           => $key,
            'name'          => $key,
            'category'      => 'Matrículas e cadastros',
            'type'          => $type,
            'value'         => $value,
            'default_value' => $value,
            'description'   => 'Parâmetro de teste.',
            'is_active'     => true,
            'is_system'     => true,
        ]);
    }

}
