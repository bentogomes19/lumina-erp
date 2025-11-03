<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Se não existe a tabela, não faz nada.
        if (! Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            // Adiciona FKs com try/catch para não falhar se já existirem
            try {
                $table->foreign('student_id')
                    ->references('id')->on('students')
                    ->cascadeOnDelete();
            } catch (\Throwable $e) {}

            try {
                // sua tabela é 'classes' (não 'school_classes')
                $table->foreign('class_id')
                    ->references('id')->on('classes')
                    ->cascadeOnDelete();
            } catch (\Throwable $e) {}

            try {
                $table->foreign('subject_id')
                    ->references('id')->on('subjects')
                    ->nullOnDelete();
            } catch (\Throwable $e) {}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            // Nomes padrão das FKs geradas pelo Laravel
            try { $table->dropForeign('attendances_student_id_foreign'); } catch (\Throwable $e) {}
            try { $table->dropForeign('attendances_class_id_foreign'); } catch (\Throwable $e) {}
            try { $table->dropForeign('attendances_subject_id_foreign'); } catch (\Throwable $e) {}
        });
    }
};
