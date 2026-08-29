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
        Schema::table('assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('assessments', 'scheduled_at')) {
                $table->dateTime('scheduled_at')->index()->after('title');
            }
            if (!Schema::hasColumn('assessments', 'weight')) {
                $table->decimal('weight', 3, 1)->default(1.0)->after('scheduled_at');
            }
        });
    }

    /**
     * Reverte as alterações realizadas pela migração.
     *
     * @return void
     */
    public function down(): void {
        Schema::table('assessments', function (Blueprint $table) {
            if (Schema::hasColumn('assessments', 'weight')) {
                $table->dropColumn('weight');
            }
            if (Schema::hasColumn('assessments', 'scheduled_at')) {
                $table->dropColumn('scheduled_at');
            }

        });
    }
};
