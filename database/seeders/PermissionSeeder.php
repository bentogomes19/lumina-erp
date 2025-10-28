<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'alunos' => ['ver', 'criar', 'editar', 'excluir'],
            'professores' => ['ver', 'criar', 'editar', 'excluir'],
            'financeiro' => ['ver', 'criar', 'editar', 'excluir'],
        ];

        foreach ($permissions as $modulo => $acoes) {
            foreach ($acoes as $acao) {
                Permission::firstOrCreate(['name' => "{$acao} {$modulo}"]);
            }
        }
    }
}
