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
            if (! Schema::hasColumn('students','status')) {
                $t->enum('status', ['active','inactive','suspended','graduated'])
                    ->default('active')
                    ->after('father_name');
            }

            if (! Schema::hasColumn('students','status_changed_at')) {
                $t->timestamp('status_changed_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('students','photo_url')) {
                $t->string('photo_url')->nullable()->after('status_changed_at');
            }

            if (! Schema::hasColumn('students','address_district')) {
                $t->string('address_district')->nullable()->after('address');
            }

            if (! Schema::hasColumn('students','guardian_main')) {
                $t->string('guardian_main')->nullable()->after('father_name');
            }
            if (! Schema::hasColumn('students','guardian_phone')) {
                $t->string('guardian_phone')->nullable()->after('guardian_main');
            }
            if (! Schema::hasColumn('students','guardian_email')) {
                $t->string('guardian_email')->nullable()->after('guardian_phone');
            }

            if (! Schema::hasColumn('students','transport_mode')) {
                $t->enum('transport_mode',['none','car','bus','van','walk','bike'])
                    ->default('none')
                    ->after('postal_code');
            }
            if (! Schema::hasColumn('students','has_special_needs')) {
                $t->boolean('has_special_needs')->default(false)->after('transport_mode');
            }
            if (! Schema::hasColumn('students','allergies')) {
                $t->string('allergies')->nullable()->after('has_special_needs');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $t) {
            foreach ([
                         'status','status_changed_at','photo_url',
                         'address_district','guardian_main','guardian_phone','guardian_email',
                         'transport_mode','has_special_needs','allergies',
                     ] as $col) {
                if (Schema::hasColumn('students',$col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
