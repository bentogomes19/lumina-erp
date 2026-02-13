<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $names = brazilian_names();
        $gender = $this->faker->randomElement(['M', 'F']);
        
        // Nome do aluno baseado no gênero
        $studentName = $gender === 'F' 
            ? $this->faker->randomElement($names['female'])
            : $this->faker->randomElement($names['male']);
        
        // Email realístico do aluno
        $emailUser = strtolower(str_replace(' ', '.', $this->removeAccents($studentName)));
        $domain = $this->faker->randomElement(email_domains());
        $studentEmail = $emailUser . rand(100, 9999) . '@' . $domain;
        
        // Nomes dos pais
        $motherName = $this->faker->randomElement($names['female']);
        $fatherName = $this->faker->randomElement($names['male']);
        
        // Email do responsável
        $guardianName = $this->faker->randomElement([$motherName, $fatherName]);
        $guardianEmailUser = strtolower(str_replace(' ', '.', $this->removeAccents($guardianName)));
        $guardianEmail = $guardianEmailUser . rand(10, 99) . '@' . $this->faker->randomElement(email_domains());
        
        // Localização brasileira
        $cities = brazilian_cities();
        $state = $this->faker->randomElement(array_keys($cities));
        $city = $this->faker->randomElement($cities[$state]);
        $street = $this->faker->randomElement(brazilian_streets());
        $district = $this->faker->randomElement(brazilian_districts());
        
        // Data de nascimento apropriada para estudantes (6 a 18 anos)
        $birthDate = $this->faker->dateTimeBetween('-18 years', '-6 years')->format('Y-m-d');
        
        return [
            'uuid'              => $this->faker->uuid,
            'user_id'           => User::factory(),
            'registration_number' => 'ALU-' . date('Y') . $this->faker->unique()->numerify('####'),
            'name'              => $studentName,
            'birth_date'        => $birthDate,
            'gender'            => $gender,
            'cpf'               => generate_cpf(),
            'rg'                => generate_rg(),
            'email'             => $studentEmail,
            'phone_number'      => brazilian_phone(true),
            'address'           => $street . ', ' . $this->faker->buildingNumber(),
            'city'              => $city,
            'state'             => $state,
            'postal_code'       => $this->faker->numerify('#####-###'),
            'mother_name'       => $motherName,
            'father_name'       => $fatherName,
            'status'            => 'active',
            'enrollment_date'   => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'exit_date'         => null,
            'meta'              => json_encode([]),
            'address_district'  => $district,
            'birth_city'        => $this->faker->randomElement($cities[$state]),
            'birth_state'       => $state,
            'nationality'       => 'Brasileira',
            'guardian_main'     => $guardianName,
            'guardian_phone'    => brazilian_phone(true),
            'guardian_email'    => $guardianEmail,
            'transport_mode'    => $this->faker->randomElement(['none', 'car', 'bus', 'van', 'walk', 'bike']),
            'has_special_needs' => $this->faker->boolean(5), // 5% de chance
            'medical_notes'     => $this->faker->boolean(10) ? $this->faker->sentence() : null,
            'allergies'         => $this->faker->boolean(8) ? $this->faker->randomElement([
                'Lactose', 'Glúten', 'Amendoim', 'Frutos do mar', 'Nenhuma alergia alimentar conhecida'
            ]) : null,
            'status_changed_at' => now(),
            'photo_url'         => null,
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
