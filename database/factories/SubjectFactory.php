<?php

namespace Database\Factories;

use App\Enums\SubjectCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            SubjectCategory::LINGUAGENS,
            SubjectCategory::MATEMATICA,
            SubjectCategory::CIENCIAS_EXATAS,
            SubjectCategory::CIENCIAS_HUMANAS,
        ];

        $subjectsMap = [
            SubjectCategory::LINGUAGENS->value => [
                'Português' => 'PT',
                'Inglês' => 'EN',
                'Espanhol' => 'ES',
                'Francês' => 'FR',
                'Artes' => 'AR',
                'Educação Física' => 'EF',
                'Redação' => 'RED',
            ],
            SubjectCategory::MATEMATICA->value => [
                'Matemática' => 'MAT',
                'Álgebra' => 'ALG',
                'Geometria' => 'GEO',
                'Trigonometria' => 'TRI',
                'Estatística' => 'STAT',
            ],
            SubjectCategory::CIENCIAS_EXATAS->value => [
                'Física' => 'FIS',
                'Química' => 'QUI',
                'Biologia' => 'BIO',
                'Ciências' => 'CI',
            ],
            SubjectCategory::CIENCIAS_HUMANAS->value => [
                'História' => 'HIS',
                'Geografia' => 'GEO',
                'Sociologia' => 'SOC',
                'Filosofia' => 'FIL',
                'Economia' => 'ECO',
            ],
        ];

        $category = $this->faker->randomElement($categories);
        $categoryValue = $category->value;
        $availableSubjects = $subjectsMap[$categoryValue];
        
        $subjectName = $this->faker->randomElement(array_keys($availableSubjects));
        $code = $availableSubjects[$subjectName];

        return [
            'code' => strtoupper($code),
            'normalized_code' => strtolower($code),
            'name' => $subjectName,
            'category' => $category,
            'status' => 'active',
            'bncc_code' => strtoupper($code),
            'bncc_reference_url' => 'https://bncc.mec.gov.br/',
            'tags' => json_encode(['educação', 'aprendizagem']),
        ];
    }
}
