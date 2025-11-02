<?php

namespace Database\Seeders;

use App\Enums\GradeLevelName;
use Illuminate\Database\Seeder;
use App\Models\GradeLevel;

class GradeLevelSeeder extends Seeder
{
    public function run(): void
    {
        foreach (GradeLevelName::cases() as $index => $gradeLevelEnum) {
            GradeLevel::firstOrCreate(
                ['name' => $gradeLevelEnum->value],
                [
                    'stage' => $gradeLevelEnum->stage(),
                    'display_order' => $index + 1,
                ]
            );
        }
    }
}
