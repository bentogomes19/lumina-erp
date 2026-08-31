<?php

namespace Tests\Feature\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use App\Services\Enrollments\StudentEnrollmentService;
use Filament\Facades\Filament;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EnrollmentCapacityTest extends TestCase {

    use RefreshDatabase;

    /**
     * Prepara o painel usado pelos cenários que renderizam recursos administrativos.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('lumina'));
    }

    /**
     * Verifica que uma turma cheia rejeita matrícula com uma mensagem detalhada.
     *
     * @return void
     */
    public function test_full_class_rejects_enrollment_with_capacity_details(): void {
        $schoolClass = $this->schoolClass(capacity: 1);
        $this->enrollment($schoolClass, EnrollmentStatus::ACTIVE);

        $this->assertValidationMessage(
            fn () => $this->service()->enrollExistingStudent([
                'class_id'   => $schoolClass->id,
                'student_id' => $this->student()->id,
                'status'     => EnrollmentStatus::ACTIVE,
            ]),
            'A turma Turma 2026 atingiu a capacidade de 1 vaga. Ocupação atual: 1.',
        );

        $this->assertDatabaseCount('enrollments', 1);
    }

    /**
     * Verifica que uma turma sem capacidade definida aceita matrículas ilimitadas.
     *
     * @return void
     */
    public function test_class_without_capacity_remains_unlimited(): void {
        $schoolClass = $this->schoolClass(capacity: null);

        foreach (range(1, 3) as $number) {
            $this->service()->enrollExistingStudent([
                'class_id'   => $schoolClass->id,
                'student_id' => $this->student("Aluno {$number}")->id,
                'status'     => EnrollmentStatus::ACTIVE,
            ]);
        }

        $this->assertSame(3, $this->service()->occupiedSlots($schoolClass));
        $this->assertNull($this->service()->remainingSlots($schoolClass));
        $this->assertSame('Ocupação: 3 | Vagas: ilimitadas', $this->service()->capacitySummary($schoolClass));
    }

    /**
     * Verifica que somente uma matrícula consome a última vaga disponível.
     *
     * @return void
     */
    public function test_only_one_enrollment_consumes_the_last_slot(): void {
        $schoolClass = $this->schoolClass(capacity: 2);
        $this->enrollment($schoolClass, EnrollmentStatus::SUSPENDED);

        $created = $this->service()->enrollExistingStudent([
            'class_id'   => $schoolClass->id,
            'student_id' => $this->student('Aluno da Última Vaga')->id,
            'status'     => EnrollmentStatus::ACTIVE,
        ]);

        $this->assertNotNull($created->id);
        $this->assertSame(2, $this->service()->occupiedSlots($schoolClass));
        $this->assertSame(0, $this->service()->remainingSlots($schoolClass));

        $this->assertValidationMessage(
            fn () => $this->service()->enrollExistingStudent([
                'class_id'   => $schoolClass->id,
                'student_id' => $this->student('Aluno Excedente')->id,
                'status'     => EnrollmentStatus::ACTIVE,
            ]),
            'A turma Turma 2026 atingiu a capacidade de 2 vagas. Ocupação atual: 2.',
        );
    }

    /**
     * Verifica que a transferência para uma turma cheia é revertida integralmente.
     *
     * @return void
     */
    public function test_transfer_to_full_class_is_rejected_atomically(): void {
        $schoolYear  = $this->schoolYear(2026);
        $sourceClass = $this->schoolClass(capacity: 30, schoolYear: $schoolYear, name: 'Turma Origem');
        $targetClass = $this->schoolClass(capacity: 1, schoolYear: $schoolYear, name: 'Turma Destino');
        $source      = $this->enrollment($sourceClass, EnrollmentStatus::ACTIVE);
        $this->enrollment($targetClass, EnrollmentStatus::LOCKED);

        $this->assertValidationMessage(
            fn () => $this->service()->transfer($source, $targetClass->id, 'Mudança de turno'),
            'A turma Turma Destino atingiu a capacidade de 1 vaga. Ocupação atual: 1.',
        );

        $this->assertSame(EnrollmentStatus::ACTIVE, $source->fresh()->status);
        $this->assertDatabaseCount('enrollments', 2);
    }

    /**
     * Verifica que rematrícula também respeita a lotação da turma de destino.
     *
     * @return void
     */
    public function test_reenrollment_to_full_class_is_rejected(): void {
        $sourceClass = $this->schoolClass(capacity: 30, schoolYear: $this->schoolYear(2025));
        $targetClass = $this->schoolClass(capacity: 1, schoolYear: $this->schoolYear(2026));
        $source      = $this->enrollment($sourceClass, EnrollmentStatus::COMPLETED);
        $this->enrollment($targetClass, EnrollmentStatus::ACTIVE);

        $this->assertValidationMessage(
            fn () => $this->service()->reenroll($source, $targetClass->id, $targetClass->school_year_id),
            'A turma Turma 2026 atingiu a capacidade de 1 vaga. Ocupação atual: 1.',
        );

        $this->assertDatabaseCount('enrollments', 2);
    }

    /**
     * Verifica quais status ocupam vaga e que a mudança de status libera e retoma a vaga.
     *
     * @return void
     */
    public function test_statuses_consume_release_and_retake_slots(): void {
        $schoolClass = $this->schoolClass(capacity: 3);
        $active      = $this->enrollment($schoolClass, EnrollmentStatus::ACTIVE);
        $this->enrollment($schoolClass, EnrollmentStatus::SUSPENDED);
        $this->enrollment($schoolClass, EnrollmentStatus::LOCKED);
        $canceled = $this->enrollment($schoolClass, EnrollmentStatus::CANCELED);
        $this->enrollment($schoolClass, EnrollmentStatus::TRANSFERRED_INTERNAL);
        $this->enrollment($schoolClass, EnrollmentStatus::TRANSFERRED_EXTERNAL);
        $this->enrollment($schoolClass, EnrollmentStatus::COMPLETED);

        $this->assertSame([
            EnrollmentStatus::ACTIVE->value,
            EnrollmentStatus::SUSPENDED->value,
            EnrollmentStatus::LOCKED->value,
        ], EnrollmentStatus::occupyingValues());
        $this->assertSame(3, $this->service()->occupiedSlots($schoolClass));

        $this->assertValidationMessage(
            fn () => $this->service()->updateStatus($canceled, EnrollmentStatus::ACTIVE),
            'A turma Turma 2026 atingiu a capacidade de 3 vagas. Ocupação atual: 3.',
        );

        $this->service()->cancel($active, 'Teste de liberação de vaga.');
        $this->service()->restoreCanceled($canceled, 'Teste de retomada de vaga.');

        $this->assertSame(3, $this->service()->occupiedSlots($schoolClass));
        $this->assertSame(EnrollmentStatus::CANCELED, $active->fresh()->status);
        $this->assertSame(EnrollmentStatus::ACTIVE, $canceled->fresh()->status);
    }

    /**
     * Verifica que a disputa de vaga bloqueia a linha da turma antes da contagem.
     *
     * @return void
     */
    public function test_last_slot_flow_uses_database_row_lock(): void {
        $schoolClass = $this->schoolClass(capacity: 1);
        $queries     = [];

        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $this->service()->enrollExistingStudent([
            'class_id'   => $schoolClass->id,
            'student_id' => $this->student()->id,
            'status'     => EnrollmentStatus::ACTIVE,
        ]);

        $lockedClass = collect($queries)->contains(
            fn (string $query): bool => str_contains($query, 'from `classes`')
                && str_contains($query, 'for update'),
        );

        $this->assertTrue($lockedClass, 'A turma deve ser bloqueada antes da disputa pela última vaga.');
    }

    /**
     * Verifica que as telas administrativas renderizam os indicadores de vagas.
     *
     * @return void
     */
    public function test_administrative_capacity_screens_render_successfully(): void {
        $role = Role::create([
            'name'       => 'admin',
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create([
            'force_password_change' => false,
        ]);
        $user->syncRoles([$role]);
        $this->schoolClass(capacity: 20);

        $this->actingAs($user)
            ->get('/lumina/school-classes')
            ->assertOk()
            ->assertSee('Ocupação')
            ->assertSee('Vagas restantes');

        $this->actingAs($user)
            ->get('/lumina/enrollments/create')
            ->assertOk();
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
     * Cria uma turma para os cenários de capacidade.
     *
     * @param int|null $capacity
     * @param SchoolYear|null $schoolYear
     * @param string|null $name
     *
     * @return SchoolClass
     */
    private function schoolClass(
        ?int $capacity,
        ?SchoolYear $schoolYear = null,
        ?string $name = null,
    ): SchoolClass {
        $schoolYear ??= $this->schoolYear(2026);
        $gradeLevel = GradeLevel::query()->firstOrCreate([
            'name' => '1º Ano Fundamental',
        ], [
            'stage'         => 'fundamental_i',
            'display_order' => 1,
        ]);

        return SchoolClass::create([
            'uuid'           => (string) Str::uuid(),
            'name'           => $name ?? "Turma {$schoolYear->year}",
            'grade_level_id' => $gradeLevel->id,
            'school_year_id' => $schoolYear->id,
            'shift'          => 'morning',
            'type'           => 'regular',
            'capacity'       => $capacity,
            'status'         => 'open',
        ]);
    }

    /**
     * Cria ou retorna um ano letivo para os cenários acadêmicos.
     *
     * @param int $year
     *
     * @return SchoolYear
     */
    private function schoolYear(int $year): SchoolYear {
        return SchoolYear::query()->firstOrCreate(['year' => $year], [
            'starts_at' => "{$year}-02-01",
            'ends_at'   => "{$year}-12-20",
            'is_active' => $year === 2026,
            'status'    => 'ativo',
        ]);
    }

    /**
     * Cria um aluno mínimo sem conta de acesso para os testes de matrícula.
     *
     * @param string|null $name
     *
     * @return Student
     */
    private function student(?string $name = null): Student {
        $uuid = (string) Str::uuid();

        return Student::create([
            'uuid'                => $uuid,
            'registration_number' => 'ALU-'.Str::upper(Str::substr($uuid, 0, 8)),
            'name'                => $name ?? 'Aluno de Teste',
            'status'              => 'active',
        ]);
    }

    /**
     * Cria uma matrícula de apoio com o status informado.
     *
     * @param SchoolClass $schoolClass
     * @param EnrollmentStatus $status
     *
     * @return Enrollment
     */
    private function enrollment(SchoolClass $schoolClass, EnrollmentStatus $status): Enrollment {
        return Enrollment::create([
            'student_id'      => $this->student()->id,
            'class_id'        => $schoolClass->id,
            'school_year_id'  => $schoolClass->school_year_id,
            'enrollment_date' => now(),
            'status'          => $status,
        ]);
    }

    /**
     * Confirma que uma operação falhou com a mensagem de capacidade esperada.
     *
     * @param callable(): mixed $operation
     * @param string $message
     *
     * @return void
     */
    private function assertValidationMessage(callable $operation, string $message): void {
        try {
            $operation();
            $this->fail('A operação deveria ter sido rejeitada pela capacidade da turma.');
        } catch (ValidationException $exception) {
            $this->assertSame($message, $exception->errors()['class_id'][0]);
        }
    }

}
