<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            DB::statement("ALTER TABLE users MODIFY gender ENUM('M','F','O') NULL");

            // 2) Converte valores legados (pt-BR) para M/F/O
            DB::table('users')->where('gender', 'Masculino')->update(['gender' => 'M']);
            DB::table('users')->where('gender', 'Feminino')->update(['gender' => 'F']);
            DB::table('users')->where('gender', 'Outro')->update(['gender' => 'O']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            DB::statement("ALTER TABLE users MODIFY gender ENUM('Masculino','Feminino','Outro') NULL");

            // Reverte os dados
            DB::table('users')->where('gender', 'M')->update(['gender' => 'Masculino']);
            DB::table('users')->where('gender', 'F')->update(['gender' => 'Feminino']);
            DB::table('users')->where('gender', 'O')->update(['gender' => 'Outro']);
        });
    }
};
