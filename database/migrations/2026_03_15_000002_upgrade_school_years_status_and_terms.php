<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona coluna status ao school_years
        Schema::table('school_years', function (Blueprint $table) {
            if (! Schema::hasColumn('school_years', 'status')) {
                $table->string('status', 20)
                    ->default('planejamento')
                    ->after('is_active')
                    ->comment('planejamento | ativo | encerrado');
            }
        });

        // Migra is_active => status
        DB::statement("
            UPDATE school_years
            SET status = CASE WHEN is_active = 1 THEN 'ativo' ELSE 'planejamento' END
            WHERE status = 'planejamento'
        ");

        // Cria tabela de períodos avaliativos
        if (! Schema::hasTable('school_year_terms')) {
            Schema::create('school_year_terms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_year_id')
                    ->constrained('school_years')
                    ->cascadeOnDelete();
                $table->string('name', 50)
                    ->comment('Ex.: 1º Bimestre, 1º Semestre');
                $table->string('type', 20)
                    ->comment('bimestre | trimestre | semestre | anual');
                $table->unsignedTinyInteger('sequence')
                    ->default(1)
                    ->comment('Ordem do período dentro do ano letivo');
                $table->date('starts_at');
                $table->date('ends_at');
                $table->date('grade_entry_starts_at')->nullable()
                    ->comment('Início do lançamento de notas');
                $table->date('grade_entry_ends_at')->nullable()
                    ->comment('Fim do lançamento de notas');
                $table->boolean('grades_published')->default(false)
                    ->comment('Notas publicadas para o portal do aluno');
                $table->timestamps();

                $table->unique(['school_year_id', 'sequence'], 'syt_year_seq_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_year_terms');

        Schema::table('school_years', function (Blueprint $table) {
            if (Schema::hasColumn('school_years', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
