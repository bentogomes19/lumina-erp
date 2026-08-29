<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {

    /**
     * Aplica as alterações definidas pela migração.
     *
     * @return void
     */
    public function up(): void {
        DB::table('students')->whereNull('has_special_needs')->update(['has_special_needs' => 0]);
        Schema::table('students', function (Blueprint $t) {
            $t->boolean('has_special_needs')->default(false)->nullable(false)->change();
        });
    }

    /**
     * Reverte as alterações realizadas pela migração.
     *
     * @return void
     */
    public function down(): void {
        Schema::table('students', function (Blueprint $t) {
            $t->boolean('has_special_needs')->default(false)->nullable()->change();
        });
    }
};
