<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Registra a data da última troca de senha, usada pela expiração de senha.
     *
     * Usuários que já definiram senha começam a contar a partir desta migração,
     * para que ativar a expiração não bloqueie todos de uma só vez.
     *
     * @return void
     */
    public function up(): void {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('password_changed_at')->nullable()->after('force_password_change');
        });

        DB::table('users')
            ->where('force_password_change', false)
            ->update(['password_changed_at' => now()]);
    }

    /**
     * Remove a coluna de data da última troca de senha.
     *
     * @return void
     */
    public function down(): void {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('password_changed_at');
        });
    }

};
