<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {

    /**
     * Aplica as alterações definidas pela migração.
     *
     * @return void
     */
    public function up(): void {
        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->boolean('curriculum_exception')->default(false)->after('subject_id');
            $table->text('curriculum_exception_justification')->nullable()->after('curriculum_exception');
        });
    }

    /**
     * Reverte as alterações realizadas pela migração.
     *
     * @return void
     */
    public function down(): void {
        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'curriculum_exception',
                'curriculum_exception_justification',
            ]);
        });
    }
};
