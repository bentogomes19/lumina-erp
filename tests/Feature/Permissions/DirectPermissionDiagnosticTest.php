<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DirectPermissionDiagnosticTest extends TestCase {

    private string $originalConnection;

    /**
     * Cria um banco SQLite mínimo para exercitar o comando sem depender das migrations completas.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        $this->originalConnection = (string) config('database.default');
        config([
            'database.default' => 'permission_command_test',
            'database.connections.permission_command_test' => [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('permission_command_test');
        DB::setDefaultConnection('permission_command_test');

        $this->createPermissionSchema();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Restaura a conexão original depois do teste isolado.
     *
     * @return void
     */
    protected function tearDown(): void {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        DB::purge('permission_command_test');
        config(['database.default' => $this->originalConnection]);
        DB::setDefaultConnection($this->originalConnection);

        parent::tearDown();
    }

    /**
     * Garante que o diagnóstico em simulação não altere permissões diretas.
     *
     * @return void
     */
    public function test_diagnostic_dry_run_reports_direct_permissions_without_changes(): void {
        [$user, $redundant, $exception] = $this->userWithDirectPermissions();

        $exitCode = Artisan::call('permissions:direct', ['--dry-run' => true]);
        $output   = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Redundante', $output);
        $this->assertStringContainsString('Exceção direta', $output);
        $this->assertDatabaseHas('model_has_permissions', [
            'permission_id' => $redundant->id,
            'model_type'    => User::class,
            'model_id'      => $user->id,
        ]);
        $this->assertDatabaseHas('model_has_permissions', [
            'permission_id' => $exception->id,
            'model_type'    => User::class,
            'model_id'      => $user->id,
        ]);
    }

    /**
     * Garante que a limpeza remova redundâncias e preserve exceções diretas.
     *
     * @return void
     */
    public function test_diagnostic_prune_removes_redundant_permissions_and_preserves_exceptions(): void {
        [$user, $redundant, $exception] = $this->userWithDirectPermissions();

        $exitCode = Artisan::call('permissions:direct', ['--prune' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseMissing('model_has_permissions', [
            'permission_id' => $redundant->id,
            'model_type'    => User::class,
            'model_id'      => $user->id,
        ]);
        $this->assertDatabaseHas('model_has_permissions', [
            'permission_id' => $exception->id,
            'model_type'    => User::class,
            'model_id'      => $user->id,
        ]);
    }

    /**
     * Cria um usuário com uma permissão redundante e uma exceção direta.
     *
     * @return array{0: User, 1: Permission, 2: Permission}
     */
    private function userWithDirectPermissions(): array {
        $redundant = Permission::create([
            'name'       => 'system.users.view',
            'guard_name' => 'web',
        ]);
        $exception = Permission::create([
            'name'       => 'system.users.block',
            'guard_name' => 'web',
        ]);
        $role = Role::create([
            'name'       => 'secretaria',
            'guard_name' => 'web',
        ]);
        $user = User::create([
            'name'     => 'Usuário direto',
            'email'    => 'direto@example.test',
            'password' => 'password',
            'active'   => true,
        ]);

        $role->givePermissionTo($redundant);
        $user->assignRole($role);
        $user->givePermissionTo([$redundant, $exception]);

        return [$user, $redundant, $exception];
    }

    /**
     * Cria as tabelas mínimas usadas pelo Spatie durante o diagnóstico.
     *
     * @return void
     */
    private function createPermissionSchema(): void {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('active')->default(true);
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
