<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Adiciona suporte a exclusão lógica aos anos letivos existentes. */
    public function up(): void
    {
        Schema::table('school_years', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    /** Remove o suporte a exclusão lógica dos anos letivos. */
    public function down(): void
    {
        Schema::table('school_years', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
