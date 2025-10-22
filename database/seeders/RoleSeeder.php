<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $teacher = Role::firstOrCreate(['name' => 'teacher']);
        $student = Role::firstOrCreate(['name' => 'student']);

        // Exemplo de permissões
        Permission::firstOrCreate(['name' => 'view students']);
        Permission::firstOrCreate(['name' => 'edit students']);
        Permission::firstOrCreate(['name' => 'view grades']);
        Permission::firstOrCreate(['name' => 'edit grades']);

        $admin->givePermissionTo(Permission::all());
        $teacher->givePermissionTo(['view students', 'edit grades', 'view grades']);
        $student->givePermissionTo(['view grades']);
    }
}
