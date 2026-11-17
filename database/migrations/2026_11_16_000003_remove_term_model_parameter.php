<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    /**
     * Remove o parâmetro "Modelo do período letivo" (o histórico é apagado em cascata).
     *
     * Notas, boletim, frequência e calendário são estruturados em 4 bimestres fixos
     * (enum Term b1–b4), então o parâmetro não tinha efeito.
     *
     * @return void
     */
    public function up(): void {
        DB::table('system_parameters')->where('key', 'academic.term_model')->delete();
    }

    /**
     * Recria o parâmetro com o valor padrão.
     *
     * @return void
     */
    public function down(): void {
        DB::table('system_parameters')->insertOrIgnore([
            'key'           => 'academic.term_model',
            'name'          => 'Modelo do período letivo',
            'category'      => 'Regras acadêmicas',
            'type'          => 'select',
            'value'         => 'bimestre',
            'default_value' => 'bimestre',
            'options'       => json_encode(['bimestre', 'trimestre', 'semestre']),
            'description'   => 'Define a organização dos períodos avaliativos e a nomenclatura exibida nos portais.',
            'is_active'     => true,
            'is_system'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

};
