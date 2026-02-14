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
        Schema::table('attendances', function (Blueprint $table) {
            // Remover constraint antiga que não considera lesson_id
            try {
                $table->dropUnique('uniq_attendance_student_class_subject_date');
            } catch (\Throwable $e) {
                // Se não existir, ignora
            }
            
            // Adicionar nova constraint incluindo lesson_id
            // Agora um aluno pode ter múltiplos registros no mesmo dia, desde que seja em aulas diferentes
            $table->unique(
                ['student_id', 'lesson_id'],
                'uniq_attendance_student_lesson'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Remover a nova constraint
            try {
                $table->dropUnique('uniq_attendance_student_lesson');
            } catch (\Throwable $e) {
                // Se não existir, ignora
            }
            
            // Recriar a constraint antiga (opcional)
            try {
                $table->unique(
                    ['student_id','class_id','subject_id','date'],
                    'uniq_attendance_student_class_subject_date'
                );
            } catch (\Throwable $e) {
                // Se não conseguir, ignora
            }
        });
    }
};
