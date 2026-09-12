<?php

namespace Database\Seeders\Academic;

use App\Models\GradeLevel;
use App\Models\SchoolClass;
use App\Models\Subject;
use Database\Seeders\Support\SchoolPopulation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SchoolClassSeeder extends Seeder {
    public function run(): void {
        SchoolPopulation::assertEnvironment();
        $subjects = Subject::whereIn('code', SchoolPopulation::SUBJECTS)->get();
        foreach (SchoolPopulation::years() as $year) {
            foreach (GradeLevel::whereIn('stage', ['fundamental_i', 'fundamental_ii'])->orderBy('display_order')->get() as $level) {
                $class = SchoolClass::firstOrCreate(['code' => sprintf('TUR-%d-%02d-A', $year->year, $level->display_order)], [
                    'uuid' => (string) Str::uuid(), 'name' => $level->display_order.'° ANO A',
                    'grade_level_id' => $level->id, 'school_year_id' => $year->id,
                    'capacity' => 35, 'shift' => $level->display_order <= 5 ? 'morning' : 'afternoon',
                    'status' => SchoolPopulation::closed($year) ? 'archived' : 'open', 'type' => 'regular',
                ]);
                foreach ($subjects as $subject) {
                    $level->subjects()->syncWithoutDetaching([$subject->id => ['hours_weekly' => 5]]);
                    $class->subjects()->syncWithoutDetaching([$subject->id]);
                }
            }
        }
    }
}
