<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GradeLevel;

class GradeLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => '1º Ano', 'order' => 1],
            ['name' => '2º Ano', 'order' => 2],
            ['name' => '3º Ano', 'order' => 3],
            ['name' => '4º Ano', 'order' => 4],
            ['name' => '5º Ano', 'order' => 5],
            ['name' => '6º Ano', 'order' => 6],
            ['name' => '7º Ano', 'order' => 7],
            ['name' => '8º Ano', 'order' => 8],
            ['name' => '9º Ano', 'order' => 9],
            ['name' => '1ª Série', 'order' => 10],
            ['name' => '2ª Série', 'order' => 11],
            ['name' => '3ª Série', 'order' => 12],
        ];

        foreach ($levels as $level) {
            GradeLevel::firstOrCreate(['name' => $level['name']], $level);
        }
    }
}
