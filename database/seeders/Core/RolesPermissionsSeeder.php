<?php

namespace Database\Seeders\Core;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $perms = [
            // Admin
            'users.view','users.create','users.update','users.delete',
            'roles.view','roles.create','roles.update','roles.delete',
            'students.view','students.create','students.update','students.delete',
            'teachers.view','teachers.create','teachers.update','teachers.delete',
            'classes.view','classes.create','classes.update','classes.delete',
            'subjects.view','subjects.create','subjects.update','subjects.delete',
            'grades.view','grades.create','grades.update','grades.delete',
            'enrollments.view','enrollments.create','enrollments.update','enrollments.delete',
            // Teacher
            'grades.view.own','grades.create.own','grades.update.own',
            'attendance.mark.own','classes.view.own','subjects.view.own',
            // Student
            'grades.view.self','subjects.view.self',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $admin   = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $teacher = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        $student = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        // Admin pega tudo
        $admin->syncPermissions(Permission::all());

        // Teacher: apenas próprias turmas/lançamentos
        $teacher->syncPermissions([
            'grades.view.own','grades.create.own','grades.update.own',
            'attendance.mark.own','classes.view.own','subjects.view.own',
        ]);

        // Student: apenas o próprio
        $student->syncPermissions([
            'grades.view.self','subjects.view.self',
        ]);
    }
}
