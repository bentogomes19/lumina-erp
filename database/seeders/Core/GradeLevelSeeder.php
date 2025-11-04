<?php

namespace Database\Seeders\Core;

use App\Enums\EducationStage;
use App\Enums\GradeLevelName;
use App\Models\GradeLevel;
use Illuminate\Database\Seeder;

class GradeLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => '1º Ano', 'stage' => EducationStage::FUND_I, 'display_order' => 1],
            ['name' => '2º Ano', 'stage' => EducationStage::FUND_I, 'display_order' => 2],
            ['name' => '3º Ano', 'stage' => EducationStage::FUND_I, 'display_order' => 3],
            ['name' => '4º Ano', 'stage' => EducationStage::FUND_I, 'display_order' => 4],
            ['name' => '5º Ano', 'stage' => EducationStage::FUND_I, 'display_order' => 5],
            ['name' => '6º Ano', 'stage' => EducationStage::FUND_II, 'display_order' => 6],
            ['name' => '7º Ano', 'stage' => EducationStage::FUND_II, 'display_order' => 7],
            ['name' => '8º Ano', 'stage' => EducationStage::FUND_II, 'display_order' => 8],
            ['name' => '9º Ano', 'stage' => EducationStage::FUND_II, 'display_order' => 9],
            ['name' => '1ª Série EM', 'stage' => EducationStage::MEDIO, 'display_order' => 10],
            ['name' => '2ª Série EM', 'stage' => EducationStage::MEDIO, 'display_order' => 11],
            ['name' => '3ª Série EM', 'stage' => EducationStage::MEDIO, 'display_order' => 12],
        ];

        foreach ($levels as $level) {
            GradeLevel::updateOrCreate(
                ['name' => $level['name']],
                [
                    'stage'         => $level['stage']->value,
                    'display_order' => $level['display_order'],
                    'description'   => $level['description'] ?? null,
                ]
            );
        }
    }
}
