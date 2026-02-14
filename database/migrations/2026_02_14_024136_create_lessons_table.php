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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Relações principais
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('school_year_id')->nullable()->constrained('school_years')->nullOnDelete();
            
            // Dados da aula
            $table->date('date')->index(); // Data da aula
            $table->time('start_time'); // Hora de início
            $table->time('end_time'); // Hora de término
            $table->unsignedInteger('duration_minutes')->nullable(); // Duração em minutos
            
            // Conteúdo pedagógico
            $table->string('topic', 200)->nullable(); // Tema da aula
            $table->text('content')->nullable(); // Conteúdo detalhado
            $table->text('objectives')->nullable(); // Objetivos de aprendizagem
            $table->text('homework')->nullable(); // Tarefa de casa
            $table->text('observations')->nullable(); // Observações gerais
            
            // Controle
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'rescheduled'])->default('scheduled');
            $table->boolean('attendance_taken')->default(false); // Chamada realizada?
            $table->timestamp('attendance_taken_at')->nullable(); // Quando a chamada foi feita
            $table->foreignId('attendance_taken_by')->nullable()->constrained('users')->nullOnDelete(); // Quem fez a chamada
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices compostos
            $table->index(['class_id', 'subject_id', 'date']);
            $table->index(['teacher_id', 'date']);
            $table->index(['date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
