<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'force_password_change')) {
                $table->boolean('force_password_change')
                    ->default(false)
                    ->after('active')
                    ->comment('Obriga troca de senha no próximo acesso');
            }

            if (! Schema::hasColumn('users', 'login_attempts')) {
                $table->unsignedTinyInteger('login_attempts')
                    ->default(0)
                    ->after('force_password_change')
                    ->comment('Tentativas consecutivas de login falhas');
            }

            if (! Schema::hasColumn('users', 'locked_at')) {
                $table->timestamp('locked_at')
                    ->nullable()
                    ->after('login_attempts')
                    ->comment('Data/hora em que o usuário foi bloqueado automaticamente');
            }

            if (! Schema::hasColumn('users', 'inactive_reason')) {
                $table->string('inactive_reason')->nullable()
                    ->after('locked_at')
                    ->comment('Motivo da inativação do usuário');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'force_password_change',
                'login_attempts',
                'locked_at',
                'inactive_reason',
            ]);
        });
    }
};
