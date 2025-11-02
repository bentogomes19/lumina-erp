<?php

namespace Database\Factories;

use App\Models\GradeLevel;
use App\Models\SchoolClass;
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
    protected $model = SchoolClass::class;

    public function definition(): array
    {
        $gradeLevel = GradeLevel::inRandomOrder()->first();

        $suffix = $this->faker->randomElement(['A', 'B', 'C', 'D']);
        $name = "{$gradeLevel->name} - {$suffix}";

        return [
            'uuid' => Str::uuid(),
            'grade_level_id' => $gradeLevel->id,
            'name' => $name,
            'shift' => $this->faker->randomElement(['Morning', 'Afternoon']),
        ];
    }

}
