<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {

    /**
     * Adiciona a chave idempotente usada pelo onboarding docente.
     *
     * @return void
     */
    public function up(): void {
        Schema::table('teachers', function (Blueprint $table): void {
            $table->uuid('onboarding_token')
                ->nullable()
                ->unique()
                ->after('uuid');
        });
    }

    /**
     * Remove a chave idempotente do onboarding docente.
     *
     * @return void
     */
    public function down(): void {
        Schema::table('teachers', function (Blueprint $table): void {
            $table->dropUnique(['onboarding_token']);
            $table->dropColumn('onboarding_token');
        });
    }
};
