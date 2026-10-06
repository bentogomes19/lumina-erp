<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void {
        Schema::table('grades', function (Blueprint $table): void {
            $table->index('enrollment_id', 'grades_enrollment_id_index');
        });

        Schema::table('grades', function (Blueprint $table): void {
            $table->dropUnique('grades_unique_entry');
        });
    }

    public function down(): void {
        Schema::table('grades', function (Blueprint $table): void {
            $table->unique(
                ['enrollment_id', 'subject_id', 'term', 'assessment_type', 'sequence'],
                'grades_unique_entry'
            );
            $table->dropIndex('grades_enrollment_id_index');
        });
    }
};
