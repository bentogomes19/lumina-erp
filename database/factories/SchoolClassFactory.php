<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'name' => $this->faker->randomElement(['1A', '2B', '3C', '4D']),
            'grade' => $this->faker->numberBetween(2023, 2025),
            'shift' => $this->faker->randomElement(['Morning', 'Afternoon']),
        ];
    }
}
