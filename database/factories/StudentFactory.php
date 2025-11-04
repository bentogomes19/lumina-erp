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
            'uuid'              => $this->faker->uuid,
            'user_id'           => User::factory(),
            'registration_number' => 'ALU-' . $this->faker->unique()->numerify('2025####'),
            'name'              => $this->faker->name,
            'birth_date'        => $this->faker->date(),
            'gender'            => $this->faker->randomElement(['M','F','O']),
            'cpf'               => $this->faker->numerify('###.###.###-##'),
            'rg'                => $this->faker->numerify('##.###.###-#'),
            'email'             => $this->faker->unique()->safeEmail,
            'phone_number'      => $this->faker->phoneNumber,
            'address'           => $this->faker->streetAddress,
            'city'              => $this->faker->city,
            'state'             => $this->faker->randomElement(['SP','RJ','MG','RS']),
            'postal_code'       => $this->faker->numerify('#####-###'),
            'mother_name'       => $this->faker->name('female'),
            'father_name'       => $this->faker->name('male'),
            'status'            => 'active',
            'enrollment_date'   => $this->faker->date(),
            'exit_date'         => null,
            'meta'              => json_encode([]),
            'address_district'  => $this->faker->citySuffix,
            'birth_city'        => $this->faker->city,
            'birth_state'       => $this->faker->randomElement(['SP','RJ','MG','RS', 'MA', 'MS', 'SC', 'PR', 'DF']),
            'nationality'       => 'Brasileira',
            'guardian_main'     => $this->faker->name,
            'guardian_phone'    => $this->faker->phoneNumber,
            'guardian_email'    => $this->faker->safeEmail,
            'transport_mode'    => $this->faker->randomElement(['none','car','bus','van','walk','bike']),
            'has_special_needs' => $this->faker->boolean(10),
            'medical_notes'     => null,
            'allergies'         => null,
            'status_changed_at' => now(),
            'photo_url'         => null,
        ];
    }
}
