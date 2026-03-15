<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Adiciona school_year_id (nullable para não quebrar dados existentes)
            if (! Schema::hasColumn('enrollments', 'school_year_id')) {
                $table->foreignId('school_year_id')
                    ->nullable()
                    ->after('class_id')
                    ->constrained('school_years')
                    ->nullOnDelete();
            }

            // Adiciona registration_number: número único e imutável por matrícula
            if (! Schema::hasColumn('enrollments', 'registration_number')) {
                $table->string('registration_number', 20)
                    ->nullable()
                    ->unique()
                    ->after('school_year_id')
                    ->comment('Número de matrícula único e imutável gerado automaticamente');
            }

            // Adiciona novos status ao enum existente
            DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM(
                'Ativa','Suspensa','Trancada','Transferida','Cancelada','Completa'
            ) NOT NULL DEFAULT 'Ativa'");
        });

        // Preenche school_year_id a partir da turma para registros existentes
        DB::statement("
            UPDATE enrollments e
            INNER JOIN classes c ON c.id = e.class_id
            SET e.school_year_id = c.school_year_id
            WHERE e.school_year_id IS NULL
              AND c.school_year_id IS NOT NULL
        ");

        // Gera registration_number para registros existentes sem ele
        $enrollments = DB::table('enrollments')->whereNull('registration_number')->orderBy('id')->get();
        foreach ($enrollments as $enrollment) {
            $year = DB::table('school_years')
                ->where('id', $enrollment->school_year_id)
                ->value('year') ?? date('Y');

            $number = str_pad($enrollment->id, 6, '0', STR_PAD_LEFT);
            DB::table('enrollments')
                ->where('id', $enrollment->id)
                ->update(['registration_number' => "{$year}{$number}"]);
        }
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'registration_number')) {
                $table->dropUnique(['registration_number']);
                $table->dropColumn('registration_number');
            }
            if (Schema::hasColumn('enrollments', 'school_year_id')) {
                $table->dropForeign(['school_year_id']);
                $table->dropColumn('school_year_id');
            }
        });

        DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM(
            'Ativa','Suspensa','Cancelada','Completa'
        ) NOT NULL DEFAULT 'Ativa'");
    }
};
