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
        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverte as alterações realizadas pela migração.
     *
     * @return void
     */
    public function down(): void {
        Schema::dropIfExists('teacher_assignments');
    }
};
