<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            // Substituir grade (texto) por referência correta
            $table->dropColumn('grade');

            // Nova FK: Série / Etapa
            $table->foreignId('grade_level_id')
                ->nullable()
                ->constrained('grade_levels')
                ->nullOnDelete();

            // Nova FK: Ano Letivo
            $table->foreignId('school_year_id')
                ->nullable()
                ->constrained('school_years')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->string('grade')->nullable();
            $table->dropConstrainedForeignId('grade_level_id');
            $table->dropConstrainedForeignId('school_year_id');
        });
    }
};
