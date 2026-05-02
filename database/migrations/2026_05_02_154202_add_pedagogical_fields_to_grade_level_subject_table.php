<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_level_subject', function (Blueprint $table) {
            if (! Schema::hasColumn('grade_level_subject', 'syllabus')) {
                $table->text('syllabus')->nullable()->after('hours_weekly');
            }

            if (! Schema::hasColumn('grade_level_subject', 'objectives')) {
                $table->text('objectives')->nullable()->after('syllabus');
            }

            if (! Schema::hasColumn('grade_level_subject', 'program_content')) {
                $table->text('program_content')->nullable()->after('objectives');
            }
        });
    }

    public function down(): void
    {
        Schema::table('grade_level_subject', function (Blueprint $table) {
            $columns = collect(['program_content', 'objectives', 'syllabus'])
                ->filter(fn (string $column) => Schema::hasColumn('grade_level_subject', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
