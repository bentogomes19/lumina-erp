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
        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); /* Ex.: "1º Ano Fundamental". */
            $table->string('stage')->default('fundamental_i'); /* Define o valor padrão sem reposicionar a coluna. */
            $table->unsignedSmallInteger('display_order')->default(1);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverte as alterações realizadas pela migração.
     *
     * @return void
     */
    public function down(): void {
        Schema::dropIfExists('grade_levels');
    }
};
