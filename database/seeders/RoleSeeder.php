<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Perfis básicos
        $admin   = Role::firstOrCreate(['name' => 'admin']);
        $teacher = Role::firstOrCreate(['name' => 'teacher']);
        $student = Role::firstOrCreate(['name' => 'student']);

        // Módulos do Lumina ERP
        $modules = [
            'users'       => ['view', 'create', 'edit', 'delete'],
            'students'    => ['view', 'create', 'edit', 'delete'],
            'teachers'    => ['view', 'create', 'edit', 'delete'],
            'classes'     => ['view', 'create', 'edit', 'delete'],
            'subjects'    => ['view', 'create', 'edit', 'delete'],
            'grades'      => ['view', 'create', 'edit', 'delete'],
            'enrollments' => ['view', 'create', 'edit', 'delete'],
            'reports'     => ['view'],
        ];

        // Cria todas as permissões (módulo + ação)
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action} {$module}"]);
            }
        }

        // Permissões por papel
        $admin->syncPermissions(Permission::all());

        $teacher->syncPermissions([
            'view students', 'view teachers', 'view classes',
            'view grades', 'create grades', 'edit grades',
        ]);

        $student->syncPermissions([
            'view grades', 'view students',
        ]);
    }
}
