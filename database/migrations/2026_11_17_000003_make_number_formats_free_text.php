<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    /**
     * Máscaras de numeração e as opções fixas que tinham como "Lista de opções".
     *
     * @var array<string, array<int, string>>
     */
    private array $formats = [
        'enrollment.number_format' => ['MAT-{year}-{sequence}', '{year}{sequence}', 'MAT-{sequence}'],
        'student.number_format'    => ['ALU-{year}-{sequence}', 'ALU-{sequence}', '{year}-{sequence}'],
        'class.number_format'      => ['TUR-{year}-{grade}-{sequence}', 'TUR-{year}-{sequence}', '{grade}-{shift}-{sequence}'],
        'teacher.number_format'    => ['PROF-{sequence}', 'FUNC-{year}-{sequence}', '{sequence}'],
    ];

    /**
     * Libera a escola para escrever a própria máscara: o tipo passa de lista para texto.
     *
     * O valor já escolhido é mantido. A atualização é feita direto na tabela porque o
     * model impede trocar o tipo de um parâmetro de sistema.
     *
     * @return void
     */
    public function up(): void {
        DB::table('system_parameters')
            ->whereIn('key', array_keys($this->formats))
            ->update(['type' => 'text', 'options' => null, 'updated_at' => now()]);
    }

    /**
     * Volta a lista de opções fixas.
     *
     * @return void
     */
    public function down(): void {
        foreach ($this->formats as $key => $options) {
            DB::table('system_parameters')
                ->where('key', $key)
                ->update(['type' => 'select', 'options' => json_encode($options), 'updated_at' => now()]);
        }
    }

};
