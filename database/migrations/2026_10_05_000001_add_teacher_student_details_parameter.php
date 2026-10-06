<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('system_parameters')->insertOrIgnore([
            'key' => 'teacher.student_details_enabled',
            'name' => 'Detalhes do aluno no Portal do Professor',
            'category' => 'Regras acadêmicas',
            'type' => 'boolean',
            'value' => '0',
            'default_value' => '0',
            'description' => 'Permite ao professor abrir dados acadêmicos e contato do responsável de alunos vinculados à aula selecionada.',
            'is_active' => true,
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('system_parameters')->where('key', 'teacher.student_details_enabled')->delete();
    }
};
