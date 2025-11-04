<?php

namespace Database\Seeders\Academic;

use App\Enums\ClassShift;
use App\Enums\ClassStatus;
use App\Enums\ClassType;
use App\Models\GradeLevel;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SchoolClassSeeder extends Seeder
{
    public function run(): void
    {
        $year = SchoolYear::first();          // ajusta conforme sua tabela
        $gradeLevels = GradeLevel::all();
        $teachers = Teacher::all();

        if (! $year || $gradeLevels->isEmpty() || $teachers->isEmpty()) {
            $this->command?->warn('SchoolClassSeeder: faltam year/gradeLevels/teachers, seeder pulado.');
            return;
        }

        foreach ($gradeLevels as $gradeLevel) {
            $randomShift = fake()->randomElement(ClassShift::cases());

            SchoolClass::create([
                'uuid'               => Str::uuid(),
                'name'               => $gradeLevel->name . ' - A',
                'code'               => Str::slug($gradeLevel->name . '-A') . '-' . $year->year,

                'shift'              => $randomShift->value,

                'homeroom_teacher_id'=> $teachers->random()->id,
                'capacity'           => 40,
                'status'            => ClassStatus::OPEN->value,
                'type'              => ClassType::REGULAR->value,

                'grade_level_id'     => $gradeLevel->id,
                'school_year_id'     => $year->id,
            ]);
        }
    }
}
