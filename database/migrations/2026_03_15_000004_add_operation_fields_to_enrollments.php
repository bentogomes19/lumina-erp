<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona campos operacionais à tabela de matrículas:
 * - Campos de trancamento (motivo, prazo)
 * - Campos de transferência (tipo, destino, motivo)
 * - Campos de cancelamento (motivo, observações)
 * - Vínculo com matrícula anterior (rematrícula)
 * - Operador responsável pela última ação
 * - Soft deletes (deleted_at)
 * - Migra status 'Transferida' → 'Transferida Interna' para consistência com novos cases do enum
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // ── Campos de trancamento ─────────────────────────────────────────
            $table->string('locked_reason', 50)->nullable()->after('status')
                ->comment('Motivo do trancamento: saude, trabalho, financeiro, outros');

            $table->date('lock_expires_at')->nullable()->after('locked_reason')
                ->comment('Prazo máximo do trancamento (padrão: fim do ano letivo)');

            // ── Campos de transferência ───────────────────────────────────────
            $table->string('transfer_type', 20)->nullable()->after('lock_expires_at')
                ->comment('Tipo de transferência: internal (entre turmas) ou external (outra instituição)');

            $table->string('transfer_destination')->nullable()->after('transfer_type')
                ->comment('Instituição de destino (somente transferência externa)');

            $table->text('transfer_reason')->nullable()->after('transfer_destination')
                ->comment('Motivo da transferência');

            // ── Campos de cancelamento ────────────────────────────────────────
            $table->text('cancel_reason')->nullable()->after('transfer_reason')
                ->comment('Motivo do cancelamento (obrigatório no ato)');

            $table->text('cancel_observations')->nullable()->after('cancel_reason')
                ->comment('Observações adicionais sobre o cancelamento');

            // ── Vínculo histórico ─────────────────────────────────────────────
            $table->unsignedBigInteger('previous_enrollment_id')->nullable()->after('cancel_observations')
                ->comment('Matrícula anterior (para rematrícula ou transferência interna)');

            $table->foreign('previous_enrollment_id')
                ->references('id')
                ->on('enrollments')
                ->nullOnDelete();

            // ── Rastreabilidade ───────────────────────────────────────────────
            $table->unsignedBigInteger('operated_by_user_id')->nullable()->after('previous_enrollment_id')
                ->comment('Último operador que realizou uma ação na matrícula');

            $table->foreign('operated_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // ── Soft deletes ──────────────────────────────────────────────────
            $table->softDeletes();
        });

        // Expande o ENUM para incluir os novos status antes de atualizar os dados.
        // A ordem importa: o ENUM precisa aceitar os novos valores antes do UPDATE.
        DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM(
            'Ativa','Suspensa','Trancada',
            'Transferida Interna','Transferida Externa',
            'Cancelada','Completa'
        ) NOT NULL DEFAULT 'Ativa'");

        // Migra status legado 'Transferida' → 'Transferida Interna'
        DB::table('enrollments')
            ->where('status', 'Transferida')
            ->update(['status' => 'Transferida Interna']);
    }

    public function down(): void
    {
        // Reverte dados antes de remover os campos e restaurar o ENUM
        DB::table('enrollments')
            ->where('status', 'Transferida Interna')
            ->update(['status' => 'Ativa']);

        DB::table('enrollments')
            ->where('status', 'Transferida Externa')
            ->update(['status' => 'Ativa']);

        // Restaura o ENUM original (sem os novos valores)
        DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM(
            'Ativa','Suspensa','Trancada','Transferida','Cancelada','Completa'
        ) NOT NULL DEFAULT 'Ativa'");

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['previous_enrollment_id']);
            $table->dropForeign(['operated_by_user_id']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'locked_reason',
                'lock_expires_at',
                'transfer_type',
                'transfer_destination',
                'transfer_reason',
                'cancel_reason',
                'cancel_observations',
                'previous_enrollment_id',
                'operated_by_user_id',
            ]);
        });
    }
};
