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
        Schema::table('students', function (Blueprint $t) {
            if (!Schema::hasColumn('students','address_district')) $t->string('address_district')->nullable();
            if (!Schema::hasColumn('students','birth_city'))       $t->string('birth_city')->nullable();
            if (!Schema::hasColumn('students','birth_state'))      $t->string('birth_state',2)->nullable();
            if (!Schema::hasColumn('students','nationality'))      $t->string('nationality')->nullable();

            if (!Schema::hasColumn('students','guardian_main'))    $t->string('guardian_main')->nullable();
            if (!Schema::hasColumn('students','guardian_phone'))   $t->string('guardian_phone')->nullable();
            if (!Schema::hasColumn('students','guardian_email'))   $t->string('guardian_email')->nullable();

            if (!Schema::hasColumn('students','transport_mode'))   $t->enum('transport_mode',['none','car','bus','van','walk','bike'])->default('none');
            if (!Schema::hasColumn('students','has_special_needs'))$t->boolean('has_special_needs')->default(false);
            if (!Schema::hasColumn('students','medical_notes'))    $t->text('medical_notes')->nullable();
            if (!Schema::hasColumn('students','allergies'))        $t->string('allergies')->nullable();

            if (!Schema::hasColumn('students','status_changed_at'))$t->timestamp('status_changed_at')->nullable();
            if (!Schema::hasColumn('students','photo_url'))        $t->string('photo_url')->nullable();

            if (Schema::hasColumn('students','cpf')) {
                // Evita duplicar o índice se já existir
                try { $t->unique('cpf','students_cpf_unique'); } catch (\Throwable $e) {}
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $t) {
            foreach ([
                         'address_district','birth_city','birth_state','nationality',
                         'guardian_main','guardian_phone','guardian_email',
                         'transport_mode','has_special_needs','medical_notes','allergies',
                         'status_changed_at','photo_url'
                     ] as $c) {
                if (Schema::hasColumn('students',$c)) $t->dropColumn($c);
            }
            try { $t->dropUnique('students_cpf_unique'); } catch (\Throwable $e) {}
        });
    }
};
