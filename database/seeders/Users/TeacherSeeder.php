<?php

namespace Database\Seeders\Users;

use App\Enums\TeacherStatus;
use App\Models\Teacher;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teachers = Teacher::factory()
            ->count(22)
            ->create();

        // atribui role "teacher" aos usuários associados
        foreach ($teachers as $teacher) {
            $teacher->user?->assignRole('teacher');
        }
    }
}
