<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $enrollments = Enrollment::all();
        $subjects = Subject::all();

        foreach ($enrollments as $enrollment) {
            foreach ($subjects->random(3) as $subject) {
                Grade::factory()->create([
                   'subject_id' => $subject->id,
                ]);
            }
        }
    }
}
