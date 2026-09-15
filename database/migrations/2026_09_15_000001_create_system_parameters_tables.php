<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('system_parameters', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name');
            $table->string('category', 80)->index();
            $table->string('type', 20);
            $table->text('value')->nullable();
            $table->text('default_value')->nullable();
            $table->json('options')->nullable();
            $table->text('description');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        Schema::create('system_parameter_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('system_parameter_id')->constrained('system_parameters')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('previous_value')->nullable();
            $table->text('new_value')->nullable();
            $table->boolean('previous_active')->default(true);
            $table->boolean('new_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_parameter_histories');
        Schema::dropIfExists('system_parameters');
    }
};
