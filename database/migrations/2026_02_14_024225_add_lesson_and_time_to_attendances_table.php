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
        Schema::table('attendances', function (Blueprint $table) {
            // Adicionar referência à aula
            $table->foreignId('lesson_id')
                ->nullable()
                ->after('subject_id')
                ->constrained('lessons')
                ->nullOnDelete();
            
            // Adicionar horário do registro de presença
            $table->time('time')
                ->nullable()
                ->after('date')
                ->comment('Hora em que a presença foi registrada');
            
            // Observações adicionais
            $table->text('notes')
                ->nullable()
                ->after('status')
                ->comment('Observações sobre a presença/falta');
            
            // Quem registrou a presença
            $table->foreignId('recorded_by')
                ->nullable()
                ->after('notes')
                ->constrained('users')
                ->nullOnDelete();
            
            // Índices
            $table->index('lesson_id');
            $table->index(['lesson_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['lesson_id']);
            $table->dropForeign(['recorded_by']);
            $table->dropIndex(['lesson_id']);
            $table->dropIndex(['lesson_id', 'status']);
            $table->dropColumn(['lesson_id', 'time', 'notes', 'recorded_by']);
        });
    }
};
