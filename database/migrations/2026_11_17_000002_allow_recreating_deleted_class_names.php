<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Permite recriar uma turma com o nome de outra que foi excluída.
     *
     * O índice anterior (ano letivo + série + nome + turno) também contava as turmas
     * excluídas, que continuam no banco como histórico. O MySQL não tem índice parcial,
     * então a coluna virtual active_name_key vale 1 para turma ativa e NULL para excluída:
     * como o índice único aceita vários NULL, só as turmas ativas precisam ter nome único.
     *
     * @return void
     */
    public function up(): void {
        /* O índice novo é criado antes de remover o antigo: as chaves estrangeiras de
         * school_year_id podem depender de um índice que comece por essa coluna. */
        Schema::table('classes', function (Blueprint $table): void {
            $table->unsignedTinyInteger('active_name_key')
                ->nullable()
                ->virtualAs('CASE WHEN deleted_at IS NULL THEN 1 END')
                ->after('deleted_at');

            $table->unique(
                ['school_year_id', 'grade_level_id', 'name', 'shift', 'active_name_key'],
                'uniq_active_class_year_grade_name_shift',
            );
        });

        Schema::table('classes', function (Blueprint $table): void {
            $table->dropUnique('uniq_class_year_grade_name_shift');
        });
    }

    /**
     * Restaura o índice anterior.
     *
     * Falha se houver turmas excluídas e ativas com o mesmo nome, série, ano e turno;
     * nesse caso é preciso renomear ou remover as duplicadas antes de reverter.
     *
     * @return void
     */
    public function down(): void {
        Schema::table('classes', function (Blueprint $table): void {
            $table->unique(['school_year_id', 'grade_level_id', 'name', 'shift'], 'uniq_class_year_grade_name_shift');
        });

        Schema::table('classes', function (Blueprint $table): void {
            $table->dropUnique('uniq_active_class_year_grade_name_shift');
            $table->dropColumn('active_name_key');
        });
    }

};
