<?php

namespace Database\Seeders;

use App\Enums\SubjectName;
use App\Models\Subject;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (SubjectName::cases() as $subjectEnum) {
            Subject::firstOrCreate(
                ['name' => $subjectEnum->value],
                [
                    'code'  => strtoupper(substr(md5($subjectEnum->value), 0, 6)),
                    'category' => $subjectEnum->category(),
                    'status'   => 'active',
                    // 'bncc_code' => $subjectEnum->bnccCode() ?? null, // se você tiver isso no enum
                ]
            );
        }
    }
}
