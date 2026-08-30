<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\Core\RolesPermissionsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermissionCatalogMigrationTest extends TestCase {

    private string $originalConnection;

    /**
     * Cria um banco SQLite mínimo e isolado para validar a conversão.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        $this->originalConnection = (string) config('database.default');
        config([
            'database.default' => 'permission_test',
            'database.connections.permission_test' => [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('permission_test');
        DB::setDefaultConnection('permission_test');

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
        DB::purge('permission_test');
        config(['database.default' => $this->originalConnection]);
        DB::setDefaultConnection($this->originalConnection);

        parent::tearDown();
    }

    /**
     * Garante que a conversão preserve atribuições de papel e de usuário sem manter o alias.
     *
     * @return void
     */
    public function test_migration_preserves_legacy_role_and_user_assignments(): void {
        $legacy = Permission::create([
            'name'       => 'students.view',
            'guard_name' => 'web',
        ]);
        $role = Role::create([
            'name'       => 'catalog-test',
            'guard_name' => 'web',
        ]);
        $user = User::create([
            'name'     => 'Usuário de teste',
            'email'    => 'catalog@example.test',
            'password' => 'password',
            'active'   => true,
        ]);

        $role->givePermissionTo($legacy);
        $user->givePermissionTo($legacy);

        $migration = require database_path('migrations/2026_08_30_000002_consolidate_permission_catalog.php');
        $migration->up();

        $this->assertDatabaseMissing('permissions', ['name' => 'students.view']);
        $this->assertTrue($role->fresh()->hasPermissionTo('academic.students.view_any'));
        $this->assertTrue($role->fresh()->hasPermissionTo('academic.students.view'));
        $this->assertTrue($user->fresh()->hasPermissionTo('academic.students.view_any'));
        $this->assertTrue($user->fresh()->hasPermissionTo('academic.students.view'));
    }

    /**
     * Garante que o seeder atribua somente permissões canônicas e preserve os portais.
     *
     * @return void
     */
    public function test_seeder_uses_only_catalog_permissions_and_separates_portals(): void {
        app(RolesPermissionsSeeder::class)->run();

        $this->assertSame(PermissionCatalog::names()->count(), Permission::query()->count());
        $this->assertDatabaseMissing('permissions', ['name' => 'grades.view.own']);

        $admin   = Role::query()->where('name', 'admin')->firstOrFail();
        $teacher = Role::query()->where('name', 'teacher')->firstOrFail();
        $student = Role::query()->where('name', 'student')->firstOrFail();

        $this->assertTrue($admin->hasPermissionTo('system.permissions.manage'));
        $this->assertFalse($admin->hasPermissionTo('teacher.dashboard.view'));
        $this->assertTrue($teacher->hasPermissionTo('teacher.dashboard.view'));
        $this->assertFalse($teacher->hasPermissionTo('student.dashboard.view'));
        $this->assertTrue($student->hasPermissionTo('student.dashboard.view'));
        $this->assertFalse($student->hasPermissionTo('teacher.dashboard.view'));
    }

    /**
     * Cria as tabelas mínimas usadas pelo Spatie durante a conversão.
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
