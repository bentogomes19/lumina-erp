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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->index();
            $table->foreignId('class_id')->index();
            $table->foreignId('subject_id')->nullable()->index();
            $table->date('date')->index();
            $table->string('status', 16)->index();
            $table->timestamps();
            $table->unique(['student_id','class_id','subject_id','date'], 'uniq_attendance_student_class_subject_date');
        });
    }

    /**
     * Reverte as alterações realizadas pela migração.
     *
     * @return void
     */
    public function down(): void {
        Schema::dropIfExists('attendances');
    }
};
