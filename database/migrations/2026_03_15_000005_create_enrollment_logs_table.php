<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela de logs de auditoria de matrículas.
 * Registra todas as operações realizadas: criação, edição, trancamento,
 * cancelamento, transferência, reativação e reversão.
 * Registros são imutáveis — sem updated_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('enrollment_id')
                ->comment('Matrícula referenciada');

            $table->foreign('enrollment_id')
                ->references('id')
                ->on('enrollments')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('operador_id')->nullable()
                ->comment('Usuário que executou a ação');

            $table->foreign('operador_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->string('acao', 50)
                ->comment('Tipo da ação: criacao, edicao, trancamento, cancelamento, transferencia_interna, transferencia_externa, reativacao, reversao_cancelamento');

            $table->string('status_anterior', 50)->nullable()
                ->comment('Status da matrícula antes da ação');

            $table->string('status_novo', 50)->nullable()
                ->comment('Status da matrícula após a ação');

            $table->text('observacao')->nullable()
                ->comment('Justificativa ou observação registrada pelo operador');

            $table->string('ip_origem', 45)->nullable()
                ->comment('IP de onde partiu a ação (IPv4 ou IPv6)');

            // Apenas created_at — logs são imutáveis, sem updated_at
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_logs');
    }
};
