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
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('normalized_code')->nullable()->unique()->after('code'); // para buscas/dup-check
            $table->enum('status', ['active','inactive'])->default('active')->after('description');
            $table->string('bncc_code')->nullable()->after('status');           // ex.: EF06LP01
            $table->string('bncc_reference_url')->nullable()->after('bncc_code');
            $table->json('tags')->nullable()->after('bncc_reference_url');      // flex: eixos/itens curriculares
            $table->index(['status']);
            $table->index(['category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['category']);
            $table->dropColumn(['normalized_code','status','bncc_code','bncc_reference_url','tags']);
        });
    }
};
