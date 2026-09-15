<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FOREIGN_KEY = 'enrollment_logs_enrollment_id_foreign';

    /** Substitui a cascata pela proteção da trilha de auditoria. */
    public function up(): void
    {
        $this->ensureSupportedDriver();
        $this->ensureThereAreNoOrphanLogs();

        $this->replaceForeignKey(restrict: true);
    }

    /** Restaura o comportamento anterior da chave estrangeira. */
    public function down(): void
    {
        $this->ensureSupportedDriver();

        $this->replaceForeignKey(restrict: false);
    }

    /**
     * Usa o Schema Builder, que gera DROP FOREIGN KEY no MySQL/MariaDB e
     * DROP CONSTRAINT no PostgreSQL conforme o driver da conexão.
     */
    private function replaceForeignKey(bool $restrict): void
    {
        Schema::table('enrollment_logs', function (Blueprint $table): void {
            $table->dropForeign(self::FOREIGN_KEY);
        });

        Schema::table('enrollment_logs', function (Blueprint $table) use ($restrict): void {
            $foreign = $table->foreign('enrollment_id', self::FOREIGN_KEY)
                ->references('id')
                ->on('enrollments');

            $restrict ? $foreign->restrictOnDelete() : $foreign->cascadeOnDelete();
        });
    }

    /** Impede que a troca da constraint comece quando já existem logs órfãos. */
    private function ensureThereAreNoOrphanLogs(): void
    {
        $hasOrphans = DB::table('enrollment_logs as logs')
            ->leftJoin('enrollments', 'enrollments.id', '=', 'logs.enrollment_id')
            ->whereNull('enrollments.id')
            ->exists();

        if ($hasOrphans) {
            throw new RuntimeException(
                'Não é possível restringir enrollment_logs.enrollment_id enquanto existirem logs órfãos.',
            );
        }
    }

    /** Garante comportamento explícito nos drivers mantidos pelo projeto. */
    private function ensureSupportedDriver(): void
    {
        $driver = DB::connection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb', 'pgsql', 'sqlite'], true)) {
            throw new RuntimeException("Driver de banco não suportado por esta migration: {$driver}.");
        }
    }
};
