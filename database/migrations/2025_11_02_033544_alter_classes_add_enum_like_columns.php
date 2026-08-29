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
        Schema::table('classes', function (Blueprint $table) {
            $table->string('shift', 32)->change();
            $table->string('status', 32)->default('open')->change();
            if (!Schema::hasColumn('classes', 'type')) {
                $table->string('type', 32)->default('regular')->after('status');
            }
            if (!Schema::hasColumn('classes', 'code')) {
                $table->string('code', 20)->nullable()->after('name');
            }
            $table->unique(['school_year_id', 'grade_level_id', 'name', 'shift'], 'uniq_class_year_grade_name_shift');
        });
    }

    /**
     * Reverte as alterações realizadas pela migração.
     *
     * @return void
     */
    public function down(): void {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropUnique('uniq_class_year_grade_name_shift');
            $table->dropColumn('type');   /* se criou. */
            $table->dropColumn('code');   /* se criou. */
        });
    }
};
