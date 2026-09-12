<?php

namespace Tests\Feature\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use App\Services\Enrollments\StudentEnrollmentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EnrollmentLifecycleRulesTest extends TestCase {

    private string $originalConnection;

    /**
     * Cria um banco SQLite mínimo para validar as regras de matrícula sem depender das migrations completas.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        $this->originalConnection = (string) config('database.default');
        config([
            'database.default' => 'enrollment_lifecycle_test',
            'database.connections.enrollment_lifecycle_test' => [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('enrollment_lifecycle_test');
        DB::setDefaultConnection('enrollment_lifecycle_test');

        $this->createSchema();
    }

    /**
     * Restaura a conexão original depois dos testes.
     *
     * @return void
     */
    protected function tearDown(): void {
        DB::purge('enrollment_lifecycle_test');
        config(['database.default' => $this->originalConnection]);
        DB::setDefaultConnection($this->originalConnection);

        parent::tearDown();
    }

    /**
     * Garante que não existam duas matrículas ativas no mesmo ano letivo.
     *
     * @return void
     */
    public function test_student_cannot_have_two_active_enrollments_in_same_school_year(): void {
        $schoolYear = $this->schoolYear(2026);
        $student    = $this->student();
        $firstClass = $this->schoolClass($schoolYear, 'Turma A');
        $nextClass  = $this->schoolClass($schoolYear, 'Turma B');

        $this->service()->enrollExistingStudent([
            'student_id' => $student->id,
            'class_id'   => $firstClass->id,
            'status'     => EnrollmentStatus::ACTIVE,
        ]);

        try {
            $this->service()->enrollExistingStudent([
                'student_id' => $student->id,
                'class_id'   => $nextClass->id,
                'status'     => EnrollmentStatus::ACTIVE,
            ]);
            $this->fail('A segunda matrícula ativa no mesmo ano deveria ser rejeitada.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'O aluno já possui matrícula ativa neste ano letivo.',
                $exception->errors()['class_id'][0],
            );
        }
    }

    /**
     * Garante que matrícula histórica na mesma turma não bloqueie novo registro.
     *
     * @return void
     */
    public function test_historical_enrollment_in_same_class_does_not_block_new_record(): void {
        $schoolYear  = $this->schoolYear(2026);
        $student     = $this->student();
        $schoolClass = $this->schoolClass($schoolYear, 'Turma A');

        Enrollment::create([
            'student_id'      => $student->id,
            'class_id'        => $schoolClass->id,
            'school_year_id'  => $schoolClass->school_year_id,
            'enrollment_date' => now(),
            'status'          => EnrollmentStatus::CANCELED,
        ]);

        $newEnrollment = $this->service()->enrollExistingStudent([
            'student_id' => $student->id,
            'class_id'   => $schoolClass->id,
            'status'     => EnrollmentStatus::ACTIVE,
        ]);

        $this->assertSame(EnrollmentStatus::ACTIVE, $newEnrollment->status);
        $this->assertDatabaseCount('enrollments', 2);
    }

    /**
     * Garante cancelamento e retorno com histórico preservado e auditado.
     *
     * @return void
     */
    public function test_cancel_and_return_preserve_history_and_audit_operator(): void {
        $operator   = User::create(['name' => 'Operador', 'email' => 'operador@example.test', 'password' => 'password']);
        $enrollment = $this->enrollment($this->schoolClass($this->schoolYear(2026), 'Turma A'));

        $canceled = $this->service()->cancel($enrollment, 'Solicitação do responsável.', 'Contato registrado.', $operator->id);

        $this->assertSame(EnrollmentStatus::CANCELED, $canceled->status);
        $this->assertSame($operator->id, $canceled->operated_by_user_id);
        $this->assertDatabaseHas('enrollment_logs', [
            'enrollment_id'   => $enrollment->id,
            'operador_id'     => $operator->id,
            'acao'            => 'cancelamento',
            'status_anterior' => EnrollmentStatus::ACTIVE->value,
            'status_novo'     => EnrollmentStatus::CANCELED->value,
        ]);

        $returned = $this->service()->restoreCanceled($canceled, 'Cancelamento revertido pela secretaria.', $operator->id);

        $this->assertSame($enrollment->id, $returned->id);
        $this->assertSame(EnrollmentStatus::ACTIVE, $returned->status);
        $this->assertDatabaseCount('enrollments', 1);
        $this->assertDatabaseHas('enrollment_logs', [
            'enrollment_id'   => $enrollment->id,
            'operador_id'     => $operator->id,
            'acao'            => 'reversao_cancelamento',
            'status_anterior' => EnrollmentStatus::CANCELED->value,
            'status_novo'     => EnrollmentStatus::ACTIVE->value,
        ]);
    }

    /**
     * Garante transferência interna e externa por fluxos centralizados.
     *
     * @return void
     */
    public function test_internal_and_external_transfers_are_centralized_and_logged(): void {
        $operator    = User::create(['name' => 'Operador', 'email' => 'transfer@example.test', 'password' => 'password']);
        $schoolYear  = $this->schoolYear(2026);
        $sourceClass = $this->schoolClass($schoolYear, 'Turma A');
        $targetClass = $this->schoolClass($schoolYear, 'Turma B');
        $source      = $this->enrollment($sourceClass);

        $newEnrollment = $this->service()->transfer($source, $targetClass->id, 'Mudança de turno.', $operator->id);

        $this->assertSame(EnrollmentStatus::TRANSFERRED_INTERNAL, $source->fresh()->status);
        $this->assertSame(EnrollmentStatus::ACTIVE, $newEnrollment->status);
        $this->assertSame($source->id, $newEnrollment->previous_enrollment_id);
        $this->assertDatabaseHas('enrollment_logs', [
            'enrollment_id' => $source->id,
            'operador_id'   => $operator->id,
            'acao'          => 'transferencia_interna',
        ]);

        $external = $this->service()->transferExternal($newEnrollment, 'Escola Destino', 'Mudança de cidade.', $operator->id);

        $this->assertSame(EnrollmentStatus::TRANSFERRED_EXTERNAL, $external->status);
        $this->assertSame('external', $external->transfer_type);
        $this->assertDatabaseHas('enrollment_logs', [
            'enrollment_id' => $newEnrollment->id,
            'operador_id'   => $operator->id,
            'acao'          => 'transferencia_externa',
        ]);
    }

    /**
     * Garante rematrícula em novo ano sem sobrescrever matrícula anterior.
     *
     * @return void
     */
    public function test_reenrollment_in_new_year_preserves_previous_enrollment(): void {
        $sourceClass = $this->schoolClass($this->schoolYear(2025), 'Turma 2025');
        $targetClass = $this->schoolClass($this->schoolYear(2026), 'Turma 2026');
        $source      = $this->enrollment($sourceClass, EnrollmentStatus::COMPLETED);

        $reenrollment = $this->service()->reenroll(
            $source,
            $targetClass->id,
            $targetClass->school_year_id,
            reason: 'Progressão anual.',
        );

        $this->assertNotSame($source->id, $reenrollment->id);
        $this->assertSame($source->id, $reenrollment->previous_enrollment_id);
        $this->assertSame(EnrollmentStatus::COMPLETED, $source->fresh()->status);
        $this->assertSame(EnrollmentStatus::ACTIVE, $reenrollment->status);
        $this->assertDatabaseCount('enrollments', 2);
        $this->assertDatabaseHas('enrollment_logs', [
            'enrollment_id' => $reenrollment->id,
            'acao'          => 'rematricula',
            'observacao'    => 'Progressão anual.',
        ]);
    }

    /**
     * Garante que transições inválidas sejam rejeitadas.
     *
     * @return void
     */
    public function test_invalid_transition_is_rejected(): void {
        $enrollment = $this->enrollment($this->schoolClass($this->schoolYear(2026), 'Turma A'), EnrollmentStatus::TRANSFERRED_EXTERNAL);

        try {
            $this->service()->updateStatus($enrollment, EnrollmentStatus::LOCKED);
            $this->fail('Transferência externa não deveria voltar para trancamento.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Use a operação específica da matrícula para esta transição.',
                $exception->errors()['status'][0],
            );
        }
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
     * Cria um ano letivo mínimo.
     *
     * @param int $year
     *
     * @return SchoolYear
     */
    private function schoolYear(int $year): SchoolYear {
        return SchoolYear::create([
            'year'      => $year,
            'starts_at' => "{$year}-02-01",
            'ends_at'   => "{$year}-12-20",
            'is_active' => $year === now()->year,
            'status'    => $year === now()->year ? 'ativo' : 'planejamento',
        ]);
    }

    /**
     * Cria uma turma mínima.
     *
     * @param SchoolYear $schoolYear
     * @param string $name
     *
     * @return SchoolClass
     */
    private function schoolClass(SchoolYear $schoolYear, string $name): SchoolClass {
        $gradeLevel = GradeLevel::query()->firstOrCreate([
            'name' => '1º Ano Fundamental',
        ], [
            'stage'         => 'fundamental_i',
            'display_order' => 1,
        ]);

        return SchoolClass::create([
            'uuid'           => (string) Str::uuid(),
            'name'           => $name,
            'grade_level_id' => $gradeLevel->id,
            'school_year_id' => $schoolYear->id,
            'shift'          => 'morning',
            'type'           => 'regular',
            'capacity'       => 30,
            'status'         => 'open',
        ]);
    }

    /**
     * Cria um aluno mínimo.
     *
     * @return Student
     */
    private function student(): Student {
        $uuid = (string) Str::uuid();

        return Student::create([
            'uuid'                => $uuid,
            'registration_number' => 'ALU-'.Str::upper(Str::substr($uuid, 0, 8)),
            'name'                => 'Aluno de Teste',
            'status'              => 'active',
        ]);
    }

    /**
     * Cria uma matrícula para testes de ciclo de vida.
     *
     * @param SchoolClass $schoolClass
     * @param EnrollmentStatus $status
     *
     * @return Enrollment
     */
    private function enrollment(
        SchoolClass $schoolClass,
        EnrollmentStatus $status = EnrollmentStatus::ACTIVE,
    ): Enrollment {
        return Enrollment::create([
            'student_id'      => $this->student()->id,
            'class_id'        => $schoolClass->id,
            'school_year_id'  => $schoolClass->school_year_id,
            'enrollment_date' => now(),
            'status'          => $status,
        ]);
    }

    /**
     * Cria as tabelas mínimas usadas nos cenários de matrícula.
     *
     * @return void
     */
    private function createSchema(): void {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
        Schema::create('grade_levels', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('stage')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
        Schema::create('school_years', function (Blueprint $table): void {
            $table->id();
            $table->integer('year')->unique();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('status')->default('planejamento');
            $table->timestamps();
        });
        Schema::create('classes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->string('name');
            $table->foreignId('grade_level_id')->nullable();
            $table->foreignId('school_year_id')->nullable();
            $table->string('shift')->nullable();
            $table->string('type')->nullable();
            $table->integer('capacity')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->string('registration_number')->unique();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id');
            $table->foreignId('class_id');
            $table->foreignId('school_year_id')->nullable();
            $table->string('registration_number')->nullable()->unique();
            $table->string('submission_token')->nullable()->unique();
            $table->date('enrollment_date')->nullable();
            $table->integer('roll_number')->nullable();
            $table->string('status')->default(EnrollmentStatus::ACTIVE->value);
            $table->string('locked_reason')->nullable();
            $table->date('lock_expires_at')->nullable();
            $table->string('transfer_type')->nullable();
            $table->string('transfer_destination')->nullable();
            $table->text('transfer_reason')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->text('cancel_observations')->nullable();
            $table->unsignedBigInteger('previous_enrollment_id')->nullable();
            $table->unsignedBigInteger('operated_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('enrollment_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enrollment_id');
            $table->unsignedBigInteger('operador_id')->nullable();
            $table->string('acao');
            $table->string('status_anterior')->nullable();
            $table->string('status_novo')->nullable();
            $table->text('observacao')->nullable();
            $table->string('ip_origem')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
}
