<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (! Schema::hasColumn('assessments', 'school_year_id')) {
                $table->foreignId('school_year_id')
                    ->nullable()
                    ->after('teacher_id')
                    ->constrained('school_years')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('assessments', 'assessment_type')) {
                $table->string('assessment_type', 30)
                    ->nullable()
                    ->after('school_year_id');
            }

            if (! Schema::hasColumn('assessments', 'max_score')) {
                $table->decimal('max_score', 5, 2)
                    ->default(10)
                    ->after('assessment_type');
            }

            if (! Schema::hasColumn('assessments', 'status')) {
                $table->string('status', 20)
                    ->default('open')
                    ->after('max_score');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (Schema::hasColumn('assessments', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('assessments', 'max_score')) {
                $table->dropColumn('max_score');
            }

            if (Schema::hasColumn('assessments', 'assessment_type')) {
                $table->dropColumn('assessment_type');
            }

            if (Schema::hasColumn('assessments', 'school_year_id')) {
                $table->dropConstrainedForeignId('school_year_id');
            }
        });
    }
};