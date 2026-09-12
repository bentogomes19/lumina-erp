<?php

namespace Database\Factories;

use App\Models\GradeLevel;
use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolClassFactory extends Factory {
    public function definition(): array {
        return [
            'uuid' => $this->faker->uuid(), 'code' => $this->faker->unique()->bothify('TEST-########'), 'name' => '5° ANO A',
            'capacity' => 35, 'shift' => 'morning', 'status' => 'open', 'type' => 'regular', 'school_year_id' => SchoolYear::factory(),
            'grade_level_id' => fn () => GradeLevel::firstOrCreate(['name' => '5º Ano'], ['stage' => 'fundamental_i', 'display_order' => 5])->id,
        ];
    }
}
