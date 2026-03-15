<?php

namespace Database\Seeders\Core;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    private const MODULES = [
        'grade_levels',
        'school_years',
        'teacher_assignments',
        'roles',
        'users',
        'classes',
        'students',
        'teachers',
        'subjects',
        'grades',
        'enrollments',
    ];

    private const ACTIONS = ['view', 'create', 'edit', 'delete', 'export'];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Gera permissões module.action
        $allAdminPerms = [];
        foreach (self::MODULES as $module) {
            foreach (self::ACTIONS as $action) {
                $perm = "{$module}.{$action}";
                Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
                $allAdminPerms[] = $perm;
            }
        }

        // Permissões específicas de professor e aluno (mantidas por retrocompatibilidade)
        $legacyPerms = [
            'grades.view.own', 'grades.create.own', 'grades.update.own',
            'attendance.mark.own', 'classes.view.own', 'subjects.view.own',
            'grades.view.self', 'subjects.view.self',
        ];
        foreach ($legacyPerms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // ── TI: acesso total a todos os módulos ──────────────────────────────────
        $ti = Role::firstOrCreate(['name' => 'ti', 'guard_name' => 'web']);
        $ti->syncPermissions(Permission::whereIn('name', $allAdminPerms)->get());

        // ── Secretaria: total na maioria, sem Perfis de Acesso, leitura em Usuários
        $secretariaPerms = [];
        foreach (self::MODULES as $module) {
            if ($module === 'roles') {
                continue;
            }
            foreach (self::ACTIONS as $action) {
                if ($module === 'users' && $action !== 'view') {
                    continue;
                }
                $secretariaPerms[] = "{$module}.{$action}";
            }
        }
        $secretaria = Role::firstOrCreate(['name' => 'secretaria', 'guard_name' => 'web']);
        $secretaria->syncPermissions(Permission::whereIn('name', $secretariaPerms)->get());

        // ── Financeiro: total em Matrículas, leitura em Ano Letivo/Turmas/Alunos ─
        $financeiroPerms = [];
        foreach (self::MODULES as $module) {
            if ($module === 'enrollments') {
                foreach (self::ACTIONS as $action) {
                    $financeiroPerms[] = "{$module}.{$action}";
                }
            } elseif (in_array($module, ['school_years', 'classes', 'students'])) {
                $financeiroPerms[] = "{$module}.view";
            }
        }
        $financeiro = Role::firstOrCreate(['name' => 'financeiro', 'guard_name' => 'web']);
        $financeiro->syncPermissions(Permission::whereIn('name', $financeiroPerms)->get());

        // ── admin: alias de TI (mantido para compatibilidade com seeders existentes)
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::whereIn('name', $allAdminPerms)->get());

        // ── teacher ──────────────────────────────────────────────────────────────
        $teacherPerms = [
            'grades.view', 'grades.create', 'grades.edit',
            'grades.view.own', 'grades.create.own', 'grades.update.own',
            'attendance.mark.own', 'classes.view.own', 'subjects.view.own',
        ];
        $teacher = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        $teacher->syncPermissions(Permission::whereIn('name', $teacherPerms)->get());

        // ── student ──────────────────────────────────────────────────────────────
        $student = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $student->syncPermissions(Permission::whereIn('name', ['grades.view.self', 'subjects.view.self'])->get());
    }
}
