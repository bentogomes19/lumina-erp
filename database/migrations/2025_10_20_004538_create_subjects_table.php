<?php

use App\Enums\SubjectCategory;
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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->enum('category', array_column(SubjectCategory::cases(), 'value'));
            $table->text('description')->nullable();
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
        Schema::dropIfExists('subjects');
    }
};
