<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Frequência é dado acadêmico histórico e não deve ser apagada
         * automaticamente quando um aluno ou turma sofre exclusão física,
         * conforme o levantamento de integridade de dados do projeto.
         */
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropForeign('attendances_student_id_foreign');
            $table->dropForeign('attendances_class_id_foreign');

            $table->foreign('student_id', 'attendances_student_id_foreign')
                ->references('id')
                ->on('students')
                ->restrictOnDelete();

            $table->foreign('class_id', 'attendances_class_id_foreign')
                ->references('id')
                ->on('classes')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropForeign('attendances_student_id_foreign');
            $table->dropForeign('attendances_class_id_foreign');

            $table->foreign('student_id', 'attendances_student_id_foreign')
                ->references('id')
                ->on('students')
                ->cascadeOnDelete();

            $table->foreign('class_id', 'attendances_class_id_foreign')
                ->references('id')
                ->on('classes')
                ->cascadeOnDelete();
        });
    }
};
