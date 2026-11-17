<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    private const PREVIOUS = 'Percentual mínimo de presença exigido para aprovação por frequência.';

    private const CURRENT = 'Percentual mínimo de presença exigido para aprovação por frequência. Não pode ser menor que 75%, mínimo exigido pela LDB (Lei nº 9.394/1996).';

    /**
     * Informa na tela de parâmetros o piso legal de 75% aplicado pelo sistema.
     *
     * @return void
     */
    public function up(): void {
        DB::table('system_parameters')
            ->where('key', 'academic.minimum_attendance')
            ->update(['description' => self::CURRENT]);
    }

    /**
     * Restaura a descrição anterior.
     *
     * @return void
     */
    public function down(): void {
        DB::table('system_parameters')
            ->where('key', 'academic.minimum_attendance')
            ->update(['description' => self::PREVIOUS]);
    }

};
