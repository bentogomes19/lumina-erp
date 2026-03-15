<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela de documentos de matrícula.
 * Controla o checklist de documentos obrigatórios e opcionais por matrícula,
 * com suporte a upload digital (PDF, JPG, PNG — máx. 10MB).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_documents', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('enrollment_id');

            $table->foreign('enrollment_id')
                ->references('id')
                ->on('enrollments')
                ->cascadeOnDelete();

            $table->string('tipo', 100)
                ->comment('Tipo do documento: rg, cpf, certidao_nascimento, comprovante_residencia, historico_escolar, etc.');

            $table->string('status', 20)->default('pendente')
                ->comment('Status do documento: entregue, pendente, dispensado');

            $table->date('data_entrega')->nullable()
                ->comment('Data em que o documento foi entregue fisicamente');

            $table->unsignedBigInteger('recebido_por_user_id')->nullable()
                ->comment('Funcionário que recebeu e registrou o documento');

            $table->foreign('recebido_por_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->text('observacoes')->nullable()
                ->comment('Observações sobre o documento (ex: cópia autenticada, original retido)');

            $table->string('arquivo_path')->nullable()
                ->comment('Caminho do arquivo digital no storage (máx. 10MB, tipos: pdf, jpg, jpeg, png)');

            $table->string('arquivo_nome_original')->nullable()
                ->comment('Nome original do arquivo enviado pelo operador');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_documents');
    }
};
