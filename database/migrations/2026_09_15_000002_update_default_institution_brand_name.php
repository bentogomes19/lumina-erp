<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('system_parameters')
            ->where('key', 'institution.name')
            ->whereIn('value', ['Escola Lumina', 'Lumina'])
            ->update([
                'value' => 'Portal Lumina',
                'default_value' => 'Portal Lumina',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('system_parameters')
            ->where('key', 'institution.name')
            ->where('value', 'Portal Lumina')
            ->update([
                'value' => 'Escola Lumina',
                'default_value' => 'Escola Lumina',
                'updated_at' => now(),
            ]);
    }
};
