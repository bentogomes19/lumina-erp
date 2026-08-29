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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('grade');
            $table->enum('shift', ['morning','afternoon','evening'])->default('morning');
            $table->foreignId('homeroom_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->integer('capacity')->nullable();
            $table->enum('status', ['open','closed','archived'])->default('open');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverte as alterações realizadas pela migração.
     *
     * @return void
     */
    public function down(): void {
        Schema::dropIfExists('classes');
    }
};
