<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $names = brazilian_names();
        $gender = $this->faker->randomElement(['M', 'F']);
        $allNames = array_merge($names['male'], $names['female']);
        $name = $this->faker->randomElement($allNames);
        
        // Gera email realístico baseado no nome
        $emailUser = strtolower(str_replace(' ', '.', $name));
        $emailUser = $this->removeAccents($emailUser);
        $domain = $this->faker->randomElement(email_domains());
        $email = $emailUser . rand(1, 999) . '@' . $domain;
        
        // Seleciona uma cidade e estado brasileiro
        $cities = brazilian_cities();
        $state = $this->faker->randomElement(array_keys($cities));
        $city = $this->faker->randomElement($cities[$state]);
        
        $street = $this->faker->randomElement(brazilian_streets());
        $district = $this->faker->randomElement(brazilian_districts());
        
        return [
            'uuid'              => $this->faker->uuid,
            'name'              => $name,
            'email'             => $email,
            'email_verified_at' => now(),
            'password'          => bcrypt('password'),
            'cpf'               => generate_cpf(),
            'rg'                => generate_rg(),
            'birth_date'        => $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'gender'            => $gender,
            'address'           => $street . ', ' . $this->faker->buildingNumber(),
            'district'          => $district,
            'city'              => $city,
            'state'             => $state,
            'postal_code'       => $this->faker->numerify('#####-###'),
            'phone'             => brazilian_phone(false),
            'cellphone'         => brazilian_phone(true),
            'avatar'            => null,
            'active'            => 1,
            'last_login_at'     => null,
            'remember_token'    => Str::random(10),
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
            'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ä' => 'A',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ö' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C', 'Ñ' => 'N',
        ];
        
        return strtr($string, $unwanted);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
