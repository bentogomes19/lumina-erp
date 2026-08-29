<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {

    /**
     * Adiciona a chave idempotente usada na criação de matrículas.
     *
     * @return void
     */
    public function up(): void {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->uuid('submission_token')
                ->nullable()
                ->unique()
                ->after('registration_number');
        });
    }

    /**
     * Remove a chave idempotente usada na criação de matrículas.
     *
     * @return void
     */
    public function down(): void {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique(['submission_token']);
            $table->dropColumn('submission_token');
        });
    }
};
