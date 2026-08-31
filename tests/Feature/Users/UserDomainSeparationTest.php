<?php

namespace Tests\Feature\Users;

use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Users\UserAccountDomainService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserDomainSeparationTest extends TestCase {

    private string $originalConnection;

    /**
     * Cria um banco SQLite mínimo para validar a separação de conta e domínio.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        $this->originalConnection = (string) config('database.default');
        config([
            'database.default' => 'user_domain_test',
            'database.connections.user_domain_test' => [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('user_domain_test');
        DB::setDefaultConnection('user_domain_test');

        $this->createSchema();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Restaura a conexão original depois dos testes.
     *
     * @return void
     */
    protected function tearDown(): void {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        DB::purge('user_domain_test');
        config(['database.default' => $this->originalConnection]);
        DB::setDefaultConnection($this->originalConnection);

        parent::tearDown();
    }

    /**
     * Garante que editar segurança e identidade da conta não altera aluno ou professor.
     *
     * @return void
     */
    public function test_user_update_does_not_change_academic_profiles(): void {
        [$user, $student, $teacher] = $this->linkedProfiles();

        $user->update([
            'name'            => 'Conta Alterada',
            'email'           => 'conta.alterada@example.test',
            'active'          => false,
            'inactive_reason' => 'Bloqueio administrativo.',
        ]);

        $this->assertSame('Aluno Original', $student->fresh()->name);
        $this->assertSame('aluno.original@example.test', $student->fresh()->email);
        $this->assertSame('active', $student->fresh()->status->value);
        $this->assertSame('Professor Original', $teacher->fresh()->name);
        $this->assertSame('professor.original@example.test', $teacher->fresh()->email);
        $this->assertSame('active', $teacher->fresh()->status->value);
    }

    /**
     * Garante que editar aluno e professor não altera silenciosamente a conta.
     *
     * @return void
     */
    public function test_academic_profile_update_does_not_change_user_account(): void {
        [$user, $student, $teacher] = $this->linkedProfiles();

        $student->update([
            'name'   => 'Aluno Alterado',
            'email'  => 'aluno.alterado@example.test',
            'status' => 'inactive',
        ]);
        $teacher->update([
            'name'   => 'Professor Alterado',
            'email'  => 'professor.alterado@example.test',
            'status' => 'inactive',
        ]);

        $this->assertSame('Conta Original', $user->fresh()->name);
        $this->assertSame('conta.original@example.test', $user->fresh()->email);
        $this->assertTrue($user->fresh()->active);
    }

    /**
     * Garante que inativar e reativar conta não altera situação acadêmica ou funcional.
     *
     * @return void
     */
    public function test_account_activation_does_not_change_academic_status(): void {
        [$user, $student, $teacher] = $this->linkedProfiles();

        $user->inactivate('Acesso suspenso temporariamente.');
        $this->assertFalse($user->fresh()->active);
        $this->assertSame('active', $student->fresh()->status->value);
        $this->assertSame('active', $teacher->fresh()->status->value);

        $user->activate();
        $this->assertTrue($user->fresh()->active);
        $this->assertSame('active', $student->fresh()->status->value);
        $this->assertSame('active', $teacher->fresh()->status->value);
    }

    /**
     * Garante que papéis administrativos mudem sem criar ou apagar entidades acadêmicas.
     *
     * @return void
     */
    public function test_administrative_role_change_does_not_create_academic_profile(): void {
        $user = User::create([
            'name'     => 'Conta Administrativa',
            'email'    => 'admin.role@example.test',
            'password' => 'password',
            'active'   => true,
        ]);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'secretaria', 'guard_name' => 'web']);
        $user->assignRole('admin');

        $this->service()->changeRole($user, 'secretaria');

        $this->assertTrue($user->fresh()->hasRole('secretaria'));
        $this->assertFalse($user->fresh()->hasRole('admin'));
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('teachers', 0);
    }

    /**
     * Garante que vínculos acadêmicos bloqueiem troca de papel incompatível.
     *
     * @return void
     */
    public function test_academic_role_change_is_rejected_for_linked_accounts(): void {
        [$studentUser] = $this->linkedStudentAccount();
        [$teacherUser] = $this->linkedTeacherAccount();
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        try {
            $this->service()->changeRole($studentUser, 'admin');
            $this->fail('Usuário de aluno não deveria aceitar papel administrativo.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Usuários vinculados a alunos devem manter o papel de aluno.',
                $exception->errors()['role'][0],
            );
        }

        try {
            $this->service()->changeRole($teacherUser, 'admin');
            $this->fail('Usuário de professor não deveria aceitar papel administrativo.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Usuários vinculados a professores devem manter o papel de professor.',
                $exception->errors()['role'][0],
            );
        }
    }

    /**
     * Garante que papéis acadêmicos não sejam concedidos fora dos onboardings.
     *
     * @return void
     */
    public function test_academic_role_is_rejected_for_unlinked_account(): void {
        $user = User::create([
            'name'     => 'Conta Sem Vínculo',
            'email'    => 'sem.vinculo@example.test',
            'password' => 'password',
            'active'   => true,
        ]);
        Role::create(['name' => 'student', 'guard_name' => 'web']);

        try {
            $this->service()->changeRole($user, 'student');
            $this->fail('Usuário sem vínculo não deveria receber papel acadêmico.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Papéis acadêmicos devem ser concedidos pelos fluxos de alunos ou professores.',
                $exception->errors()['role'][0],
            );
        }
    }

    /**
     * Garante que sincronizar identidade acadêmica só ocorre por chamada explícita.
     *
     * @return void
     */
    public function test_explicit_identity_sync_updates_only_account_identity(): void {
        [$user, $student] = $this->linkedStudentAccount();
        $student->update([
            'name'   => 'Aluno Para Sincronizar',
            'email'  => 'aluno.sincronizado@example.test',
            'status' => 'inactive',
        ]);

        $this->service()->syncAccountIdentityFromStudent($student->fresh());

        $this->assertSame('Aluno Para Sincronizar', $user->fresh()->name);
        $this->assertSame('aluno.sincronizado@example.test', $user->fresh()->email);
        $this->assertTrue($user->fresh()->active);
    }

    /**
     * Retorna o serviço exercitado pelos testes.
     *
     * @return UserAccountDomainService
     */
    private function service(): UserAccountDomainService {
        return app(UserAccountDomainService::class);
    }

    /**
     * Cria uma conta vinculada a aluno e professor para validar isolamento.
     *
     * @return array{0: User, 1: Student, 2: Teacher}
     */
    private function linkedProfiles(): array {
        $user = User::create([
            'name'     => 'Conta Original',
            'email'    => 'conta.original@example.test',
            'password' => 'password',
            'active'   => true,
        ]);
        $student = Student::create([
            'user_id'             => $user->id,
            'registration_number' => 'ALU-TESTE-001',
            'name'                => 'Aluno Original',
            'email'               => 'aluno.original@example.test',
            'status'              => 'active',
        ]);
        $teacher = Teacher::create([
            'user_id'         => $user->id,
            'employee_number' => 'DOC-TESTE-001',
            'name'            => 'Professor Original',
            'email'           => 'professor.original@example.test',
            'status'          => 'active',
        ]);

        return [$user, $student, $teacher];
    }

    /**
     * Cria uma conta vinculada a aluno com papel acadêmico.
     *
     * @return array{0: User, 1: Student}
     */
    private function linkedStudentAccount(): array {
        $user = User::create([
            'name'     => 'Aluno Conta',
            'email'    => 'aluno.conta@example.test',
            'password' => 'password',
            'active'   => true,
        ]);
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $user->assignRole('student');
        $student = Student::create([
            'user_id'             => $user->id,
            'registration_number' => 'ALU-TESTE-002',
            'name'                => 'Aluno Conta',
            'email'               => 'aluno.conta@example.test',
            'status'              => 'active',
        ]);

        return [$user, $student];
    }

    /**
     * Cria uma conta vinculada a professor com papel acadêmico.
     *
     * @return array{0: User, 1: Teacher}
     */
    private function linkedTeacherAccount(): array {
        $user = User::create([
            'name'     => 'Professor Conta',
            'email'    => 'professor.conta@example.test',
            'password' => 'password',
            'active'   => true,
        ]);
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        $user->assignRole('teacher');
        $teacher = Teacher::create([
            'user_id'         => $user->id,
            'employee_number' => 'DOC-TESTE-002',
            'name'            => 'Professor Conta',
            'email'           => 'professor.conta@example.test',
            'status'          => 'active',
        ]);

        return [$user, $teacher];
    }

    /**
     * Cria as tabelas mínimas para conta, domínio acadêmico e papéis.
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
            $table->boolean('active')->default(true);
            $table->integer('login_attempts')->default(0);
            $table->timestamp('locked_at')->nullable();
            $table->string('inactive_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('registration_number')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('teachers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('employee_number')->nullable()->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('status')->default('active');
            $table->date('termination_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });
    }
}
