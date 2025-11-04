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
        return [
            'uuid'            => $this->faker->uuid,
            'name'            => $this->faker->name,
            'email'           => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password'        => bcrypt('password'),
            'cpf'             => $this->faker->numerify('###.###.###-##'),
            'rg'              => $this->faker->numerify('##.###.###-#'),
            'birth_date'      => $this->faker->date(),
            'gender'          => $this->faker->randomElement(['M','F','O']),
            'address'         => $this->faker->streetAddress,
            'district'        => $this->faker->citySuffix,
            'city'            => $this->faker->city,
            'state'           => $this->faker->randomElement(['SP','RJ','MG','RS', 'BA', 'MA', 'MS', 'SC', 'PR', 'DF']),
            'postal_code'     => $this->faker->numerify('#####-###'),
            'phone'           => $this->faker->phoneNumber,
            'cellphone'       => $this->faker->phoneNumber,
            'avatar'          => null,
            'active'          => 1,
            'last_login_at'   => null,
            'remember_token'  => Str::random(10),
        ];
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
