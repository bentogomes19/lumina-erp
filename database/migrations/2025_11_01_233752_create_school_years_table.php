<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {

    /**
     * Aplica as alterações definidas pela migração.
     *
     * @return void
     */
    public function up(): void {
        Schema::create('school_years', function (Blueprint $table) {
            $table->id();
            $table->year('year'); /* 2025. */
            $table->date('starts_at');
            $table->date('ends_at');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverte as alterações realizadas pela migração.
     *
     * @return void
     */
    public function down(): void {
        Schema::dropIfExists('school_years');
    }
};
