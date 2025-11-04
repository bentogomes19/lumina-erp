<?php

namespace Database\Seeders\Users;

use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = Student::factory()
            ->count(100)
            ->create();

        foreach ($students as $student) {
            $student->user?->assignRole('student');
        }
    }
}
