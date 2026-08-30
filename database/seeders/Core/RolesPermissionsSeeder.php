<?php

namespace Database\Seeders\Core;

use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

class RolesPermissionsSeeder extends Seeder {

    /**
     * Cria os perfis e atribui exclusivamente permissões do catálogo canônico.
     *
     * @return void
     */
    public function run(): void {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $catalog = PermissionCatalog::all();
        foreach ($catalog as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission['name'],
                'guard_name' => 'web',
            ]);
        }

        $administrative = $catalog
            ->reject(fn (array $permission): bool => in_array($permission['module'], [
                'Portal do Aluno',
                'Portal do Professor',
                'Responsável',
            ], true));

        $this->syncRole('ti', $administrative);
        $this->syncRole('admin', $administrative);
        $this->syncRole('secretaria', $catalog->whereIn('module', [
            'Secretaria Acadêmica',
            'Professores - Administrativo',
            'Relatórios',
        ]));
        $this->syncRole('financeiro', $catalog->filter(
            fn (array $permission): bool => $permission['module'] === 'Financeiro'
                || str_starts_with($permission['name'], 'academic.enrollments.')
                || ($permission['module'] === 'Secretaria Acadêmica'
                    && in_array($permission['type'], ['view', 'view_any'], true)),
        ));
        $this->syncRole('teacher', $catalog->where('module', 'Portal do Professor'));
        $this->syncRole('student', $catalog->where('module', 'Portal do Aluno'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Sincroniza um perfil com os nomes canônicos informados.
     *
     * @param string $roleName
     * @param Collection<int, array<string, mixed>> $permissions
     *
     * @return void
     */
    private function syncRole(string $roleName, Collection $permissions): void {
        $role = Role::firstOrCreate([
            'name'       => $roleName,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($permissions->pluck('name')->all());
    }
}
