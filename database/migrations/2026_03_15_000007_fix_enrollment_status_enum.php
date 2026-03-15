<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige o ENUM de status da tabela enrollments.
 *
 * A migration 000004 adicionou os campos operacionais mas esqueceu de
 * expandir o ENUM para incluir 'Transferida Interna' e 'Transferida Externa'.
 * Esta migration aplica a correção e migra o valor legado 'Transferida'.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Expande o ENUM para incluir os novos valores de status
        DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM(
            'Ativa',
            'Suspensa',
            'Trancada',
            'Transferida Interna',
            'Transferida Externa',
            'Cancelada',
            'Completa'
        ) NOT NULL DEFAULT 'Ativa'");

        // Migra o valor legado 'Transferida' → 'Transferida Interna'
        DB::table('enrollments')
            ->where('status', 'Transferida')
            ->update(['status' => 'Transferida Interna']);
    }

    public function down(): void
    {
        // Reverte dados antes de restaurar o ENUM antigo
        DB::table('enrollments')->where('status', 'Transferida Interna')->update(['status' => 'Ativa']);
        DB::table('enrollments')->where('status', 'Transferida Externa')->update(['status' => 'Ativa']);

        DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM(
            'Ativa','Suspensa','Trancada','Transferida','Cancelada','Completa'
        ) NOT NULL DEFAULT 'Ativa'");
    }
};
