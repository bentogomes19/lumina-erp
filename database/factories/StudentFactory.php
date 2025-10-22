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
        static $counter = 0;
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(), // cria um user vinculado automaticamente
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'birth_date' => $this->faker->date('Y-m-d', '2015-12-31'),
            'gender' => $this->faker->randomElement(['Masculino', 'Feminino']),
            'cpf' => $this->faker->unique()->numerify('###.###.###-##'),
            'phone_number' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'postal_code' => $this->faker->postcode(),
            'mother_name' => $this->faker->name('female'),
            'father_name' => $this->faker->name('male'),

            // Gera matrícula única incremental, ex: 2025A001, 2025A002...
            'registration_number' => sprintf('2025A%03d', $counter++),

            'status' => 'Ativo',
            'enrollment_date' => $this->faker->date('Y-m-d', '2024-02-15'),
            'exit_date' => null,
        ];
    }
}
