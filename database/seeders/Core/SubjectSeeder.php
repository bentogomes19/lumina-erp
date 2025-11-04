<?php

namespace Database\Seeders\Core;

use App\Enums\SubjectCategory;
use App\Enums\SubjectName;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            [
                'code'               => 'LP',
                'normalized_code'    => 'lp',
                'name'               => 'Língua Portuguesa',
                'category'           => SubjectCategory::LINGUAGENS,
                'status'             => 'active',
                'bncc_code'          => 'LP',
                'bncc_reference_url' => 'https://bncc.mec.gov.br/',
                'tags'               => ['leitura', 'escrita'],
            ],
            [
                'code'               => 'MAT',
                'normalized_code'    => 'mat',
                'name'               => 'Matemática',
                'category'           => SubjectCategory::MATEMATICA,
                'status'             => 'active',
                'bncc_code'          => 'MAT',
                'bncc_reference_url' => 'https://bncc.mec.gov.br/',
                'tags'               => ['números', 'álgebra'],
            ],
            [
                'code'               => 'HIS',
                'normalized_code'    => 'his',
                'name'               => 'História',
                'category'           => SubjectCategory::CIENCIAS_HUMANAS,
                'status'             => 'active',
                'bncc_code'          => 'HIS',
                'bncc_reference_url' => 'https://bncc.mec.gov.br/',
                'tags'               => ['sociedade', 'cultura'],
            ],
            [
                'code'               => 'GEO',
                'normalized_code'    => 'geo',
                'name'               => 'Geografia',
                'category'           => SubjectCategory::CIENCIAS_HUMANAS,
                'status'             => 'active',
                'bncc_code'          => 'GEO',
                'bncc_reference_url' => 'https://bncc.mec.gov.br/',
                'tags'               => ['sociedade', 'cultura'],
            ],
            [
                'code'               => 'CIE',
                'normalized_code'    => 'cie',
                'name'               => 'Ciências',
                'category'           => SubjectCategory::CIENCIAS_HUMANAS,
                'status'             => 'active',
                'bncc_code'          => 'CIE',
                'bncc_reference_url' => 'https://bncc.mec.gov.br/',
                'tags'               => ['ciência', 'cultura'],
            ],
            [
                'code'               => 'ALG',
                'normalized_code'    => 'alg',
                'name'               => 'Álgebra',
                'category'           => SubjectCategory::CIENCIAS_EXATAS,
                'status'             => 'active',
                'bncc_code'          => 'ALG',
                'bncc_reference_url' => 'https://bncc.mec.gov.br/',
                'tags'               => ['matemática'],
            ],
            [
                'code'               => 'FILO',
                'normalized_code'    => 'fil',
                'name'               => 'Filosofia',
                'category'           => SubjectCategory::CIENCIAS_HUMANAS,
                'status'             => 'active',
                'bncc_code'          => 'FILO',
                'bncc_reference_url' => 'https://bncc.mec.gov.br/',
                'tags'               => ['sociedade', 'cultura'],
            ],
            [
                'code'               => 'SOCIO',
                'normalized_code'    => 'socio',
                'name'               => 'Sociologia',
                'category'           => SubjectCategory::CIENCIAS_HUMANAS,
                'status'             => 'active',
                'bncc_code'          => 'SOCIO',
                'bncc_reference_url' => 'https://bncc.mec.gov.br/',
                'tags'               => ['sociedade', 'cultura'],
            ],
        ];

        foreach ($subjects as $subject) {
            $data = $subject;
            $data['tags'] = json_encode($subject['tags']);

            Subject::updateOrCreate(
                [
                    'code' => $subject['code'],
                    'category' => $subject['category']->value,
                ],
                $data
            );
        }
    }
}
