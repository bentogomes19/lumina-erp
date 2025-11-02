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
        Schema::table('students', function (Blueprint $t) {
            if (! Schema::hasColumn('students', 'rg')) {
                $t->string('rg', 20)->nullable()->after('cpf');
            }
            // estes já existem no seu projeto, mas deixo idempotentes por segurança:
            if (! Schema::hasColumn('students', 'has_special_needs')) {
                $t->boolean('has_special_needs')->default(false);
            }
            if (! Schema::hasColumn('students', 'status_changed_at')) {
                $t->timestamp('status_changed_at')->nullable();
            }
            if (! Schema::hasColumn('students', 'photo_url')) {
                $t->string('photo_url')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $t) {
            if (Schema::hasColumn('students', 'rg')) $t->dropColumn('rg');
        });
    }
};
