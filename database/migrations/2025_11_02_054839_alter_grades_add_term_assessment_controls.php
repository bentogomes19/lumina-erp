<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $t) {
            if (!Schema::hasColumn('grades', 'enrollment_id')) {
                $t->foreignId('enrollment_id')->nullable()->after('id')
                    ->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('grades', 'term')) {
                $t->enum('term', array_keys(\App\Enums\Term::options()))
                    ->default('b1')->after('subject_id');
            }
            if (!Schema::hasColumn('grades', 'assessment_type')) {
                $t->enum('assessment_type', array_keys(\App\Enums\AssessmentType::options()))
                    ->default('test')->after('term');
            }
            if (!Schema::hasColumn('grades', 'sequence')) {
                $t->unsignedTinyInteger('sequence')->default(1)->after('assessment_type');
            }
            if (!Schema::hasColumn('grades', 'weight')) {
                $t->decimal('weight', 4, 2)->default(1)->after('max_score');
            }
            if (!Schema::hasColumn('grades', 'posted_by')) {
                $t->foreignId('posted_by')->nullable()->after('comment')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('grades', 'locked_at')) {
                $t->timestamp('locked_at')->nullable()->after('posted_by');
            }
            if (!Schema::hasColumn('grades', 'origin')) {
                $t->enum('origin', ['manual', 'import'])->default('manual')->after('locked_at');
            }
            if (!Schema::hasColumn('grades', 'recovery_of_id')) {
                $t->foreignId('recovery_of_id')->nullable()->after('origin')
                    ->constrained('grades')->nullOnDelete();
            }

            // índice de unicidade operacional
            $t->unique(
                ['enrollment_id', 'subject_id', 'term', 'assessment_type', 'sequence'],
                'grades_unique_entry'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $t) {
            $t->dropUnique('grades_unique_entry');
            $cols = ['recovery_of_id', 'origin', 'locked_at', 'posted_by', 'weight', 'sequence', 'assessment_type', 'term', 'enrollment_id'];
            foreach ($cols as $c) if (Schema::hasColumn('grades', $c)) $t->dropColumn($c);
        });
    }
};
