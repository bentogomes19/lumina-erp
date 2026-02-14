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
        Schema::create('school_holidays', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('school_year_id')
                ->nullable()
                ->constrained('school_years')
                ->nullOnDelete();
            
            $table->string('name'); // Nome do feriado/recesso
            $table->text('description')->nullable();
            $table->date('start_date'); // Data de início
            $table->date('end_date'); // Data de término (pode ser igual ao início)
            
            // Tipo: feriado nacional, municipal, recesso, evento escolar, etc.
            $table->enum('type', [
                'national_holiday',    // Feriado nacional
                'state_holiday',       // Feriado estadual
                'municipal_holiday',   // Feriado municipal
                'school_recess',       // Recesso escolar
                'school_event',        // Evento escolar (sem aula)
                'exam_period',         // Período de provas
                'other'                // Outro
            ])->default('national_holiday');
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Índices
            $table->index(['start_date', 'end_date']);
            $table->index(['school_year_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_holidays');
    }
};
