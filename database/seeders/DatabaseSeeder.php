<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesPermissionsSeeder::class,
            UserSeeder::class,
            GradeLevelSeeder::class,
            SubjectSeeder::class,
            SchoolClassSeeder::class,
            TeacherSeeder::class,
            GradeSeeder::class,
            StudentSeeder::class,
        ]);
    }
}
