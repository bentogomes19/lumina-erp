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
            $table->unique(['class_id', 'subject_id']);
        });
    }

    /**
     * Reverte as alterações realizadas pela migração.
     *
     * @return void
     */
    public function down(): void {
        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->dropUnique(['class_id', 'subject_id']);
        });
    }
};
