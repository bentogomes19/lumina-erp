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
        if (! Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $t) {
                $t->id();
                $t->foreignId('student_id')->index();
                $t->foreignId('class_id')->index();
                $t->foreignId('subject_id')->nullable()->index();
                $t->date('date')->index();
                $t->string('status', 16)->index(); // present|absent|late
                $t->timestamps();
                $t->unique(['student_id','class_id','subject_id','date'], 'uniq_attendance_student_class_subject_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
