<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            if (! Schema::hasColumn('grades', 'assessment_id')) {
                $table->foreignId('assessment_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('assessments')
                    ->nullOnDelete();
            }

            try {
                $table->unique(['assessment_id', 'student_id'], 'grades_unique_assessment_student');
            } catch (\Throwable $e) {
                // ignore if index already exists
            }
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            try {
                $table->dropUnique('grades_unique_assessment_student');
            } catch (\Throwable $e) {
                // ignore
            }

            if (Schema::hasColumn('grades', 'assessment_id')) {
                $table->dropConstrainedForeignId('assessment_id');
            }
        });
    }
};
