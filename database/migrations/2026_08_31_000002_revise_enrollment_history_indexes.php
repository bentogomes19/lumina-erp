<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {

    /**
     * Troca a unicidade permanente aluno/turma por índices adequados ao histórico.
     *
     * @return void
     */
    public function up(): void {
        Schema::table('enrollments', function (Blueprint $table): void {
            if (!Schema::hasIndex('enrollments', 'enr_student_fk_idx')) {
                $table->index(['student_id'], 'enr_student_fk_idx');
            }

            if (!Schema::hasIndex('enrollments', 'enr_student_year_status_idx')) {
                $table->index(['student_id', 'school_year_id', 'status'], 'enr_student_year_status_idx');
            }

            if (!Schema::hasIndex('enrollments', 'enr_student_class_status_idx')) {
                $table->index(['student_id', 'class_id', 'status'], 'enr_student_class_status_idx');
            }
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            if (Schema::hasIndex('enrollments', 'enr_student_class_unique', 'unique')) {
                $table->dropUnique('enr_student_class_unique');
            }
        });
    }

    /**
     * Restaura a unicidade anterior entre aluno e turma.
     *
     * @return void
     */
    public function down(): void {
        Schema::table('enrollments', function (Blueprint $table): void {
            if (Schema::hasIndex('enrollments', 'enr_student_year_status_idx')) {
                $table->dropIndex('enr_student_year_status_idx');
            }

            if (Schema::hasIndex('enrollments', 'enr_student_class_status_idx')) {
                $table->dropIndex('enr_student_class_status_idx');
            }
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            if (!Schema::hasIndex('enrollments', 'enr_student_class_unique', 'unique')) {
                $table->unique(['student_id', 'class_id'], 'enr_student_class_unique');
            }
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            if (Schema::hasIndex('enrollments', 'enr_student_fk_idx')) {
                $table->dropIndex('enr_student_fk_idx');
            }
        });
    }
};
