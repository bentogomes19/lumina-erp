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
        Schema::table('teachers', function (Blueprint $table) {
            // identificação
            if (!Schema::hasColumn('teachers', 'cpf')) $table->string('cpf', 14)->nullable()->unique()->after('user_id');
            if (!Schema::hasColumn('teachers', 'birth_date')) $table->date('birth_date')->nullable()->after('qualification');
            if (!Schema::hasColumn('teachers', 'gender')) $table->enum('gender', ['M', 'F', 'O'])->nullable()->after('birth_date');

            // contato/endereço
            if (!Schema::hasColumn('teachers', 'mobile')) $table->string('mobile', 20)->nullable()->after('phone');
            if (!Schema::hasColumn('teachers', 'address_street')) {
                $table->string('address_street')->nullable();
                $table->string('address_number', 10)->nullable();
                $table->string('address_district')->nullable();
                $table->string('address_city')->nullable();
                $table->string('address_state', 2)->nullable();
                $table->string('address_zip', 10)->nullable();
            }

            // emprego/regime
            if (!Schema::hasColumn('teachers', 'regime')) $table->string('regime', 20)->nullable()->after('hire_date'); // enum via cast
            if (!Schema::hasColumn('teachers', 'weekly_workload')) $table->unsignedSmallInteger('weekly_workload')->nullable()->after('regime'); // horas
            if (!Schema::hasColumn('teachers', 'max_classes')) $table->unsignedSmallInteger('max_classes')->nullable()->after('weekly_workload');

            // titulação
            if (!Schema::hasColumn('teachers', 'academic_title')) $table->string('academic_title', 20)->nullable()->after('qualification'); // enum via cast

            // governança/vida funcional
            if (!Schema::hasColumn('teachers', 'admission_date')) $table->date('admission_date')->nullable()->after('hire_date');
            if (!Schema::hasColumn('teachers', 'termination_date')) $table->date('termination_date')->nullable()->after('admission_date');
            if (!Schema::hasColumn('teachers', 'lattes_url')) $table->string('lattes_url')->nullable()->after('bio');

            // status -> guardar como enum string inglês (cast)
            $table->string('status', 20)->default('active')->change();

            // índices úteis
            $table->index(['status']);
            $table->index(['regime']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            //
        });
    }
};
