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
        Schema::table('enrollments', function (Blueprint $t) {
            if (Schema::hasColumn('enrollments', 'roll_number')) {
                $t->integer('roll_number')->nullable()->change();
            } else {
                $t->integer('roll_number')->nullable()->after('enrollment_date');
            }

            // Índices úteis
            $t->index(['class_id', 'roll_number'], 'enr_class_roll_idx');

            // Unicidade: um aluno não pode estar 2x na MESMA turma
            $t->unique(['student_id','class_id'], 'enr_student_class_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $t) {
            $t->dropIndex('enr_class_roll_idx');
            $t->dropUnique('enr_student_class_unique');
        });
    }
};
