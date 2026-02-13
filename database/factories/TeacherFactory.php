<?php

namespace Database\Factories;

use App\Enums\AcademicTitle;
use App\Enums\TeacherRegime;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $academicTitle = $this->faker->randomElement(AcademicTitle::cases());
        $teacherRegime = $this->faker->randomElement(TeacherRegime::cases());
        
        $names = brazilian_names();
        $gender = $this->faker->randomElement(['M', 'F']);
        
        // Nome do professor baseado no gênero
        $teacherName = $gender === 'F' 
            ? $this->faker->randomElement($names['female'])
            : $this->faker->randomElement($names['male']);
        
        // Email realístico do professor
        $emailUser = strtolower(str_replace(' ', '.', $this->removeAccents($teacherName)));
        $domain = $this->faker->randomElement(email_domains());
        $teacherEmail = $emailUser . rand(1, 99) . '@' . $domain;
        
        // Qualificação realística
        $qualification = $this->faker->randomElement(teacher_qualifications());
        
        // Localização brasileira
        $cities = brazilian_cities();
        $state = $this->faker->randomElement(array_keys($cities));
        $city = $this->faker->randomElement($cities[$state]);
        $street = $this->faker->randomElement(brazilian_streets());
        $district = $this->faker->randomElement(brazilian_districts());
        
        // Data de nascimento apropriada para professores (25 a 65 anos)
        $birthDate = $this->faker->dateTimeBetween('-65 years', '-25 years')->format('Y-m-d');
        
        // Data de contratação realística (últimos 20 anos)
        $hireDate = $this->faker->dateTimeBetween('-20 years', '-1 month')->format('Y-m-d');
        
        // Bio profissional realística
        $yearsExperience = $this->faker->numberBetween(2, 30);
        $bio = "Professor(a) com {$yearsExperience} anos de experiência na área de educação. " .
               "Especializado(a) em metodologias ativas e ensino personalizado.";

        return [
            'uuid'             => $this->faker->uuid,
            'user_id'          => User::factory(),
            'cpf'              => generate_cpf(),
            'employee_number'  => 'PROF-' . $this->faker->unique()->numerify('####'),
            'name'             => $teacherName,
            'qualification'    => $qualification,
            'academic_title'   => $academicTitle->value,
            'birth_date'       => $birthDate,
            'gender'           => $gender,
            'hire_date'        => $hireDate,
            'admission_date'   => $hireDate,
            'termination_date' => null,
            'regime'           => $teacherRegime->value,
            'weekly_workload'  => $this->faker->randomElement([20, 30, 40]),
            'max_classes'      => $this->faker->numberBetween(6, 15),
            'email'            => $teacherEmail,
            'phone'            => brazilian_phone(false),
            'mobile'           => brazilian_phone(true),
            'bio'              => $bio,
            'lattes_url'       => $this->faker->boolean(30) 
                ? 'http://lattes.cnpq.br/' . $this->faker->numerify('################')
                : null,
            'status'           => 'active',
            'address_street'   => $street,
            'address_number'   => $this->faker->buildingNumber(),
            'address_district' => $district,
            'address_city'     => $city,
            'address_state'    => $state,
            'address_zip'      => $this->faker->numerify('#####-###'),
        ];
    }
    
    /**
     * Remove acentos de uma string
     */
    private function removeAccents(string $string): string
    {
        $unwanted = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ];
        
        return strtr($string, $unwanted);
    }
}
