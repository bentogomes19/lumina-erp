<?php

namespace Database\Seeders;

use Database\Seeders\Academic\AttendanceSeeder;
use Database\Seeders\Academic\EnrollmentSeeder;
use Database\Seeders\Academic\GradeSeeder;
use Database\Seeders\Academic\SchoolClassSeeder;
use Database\Seeders\Academic\SchoolYearSeeder;
use Database\Seeders\Academic\TeacherAssignmentSeeder;
use Database\Seeders\Core\GradeLevelSeeder;
use Database\Seeders\Core\RolesPermissionsSeeder;
use Database\Seeders\Core\SubjectSeeder;
use Database\Seeders\Users\AdminUserSeeder;
use Database\Seeders\Users\StudentSeeder;
use Database\Seeders\Users\TeacherSeeder;
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
        // Dados de referência Fixo
        $this->call([
            GradeLevelSeeder::class,
            RolesPermissionsSeeder::class,
            SubjectSeeder::class,
            SchoolYearSeeder::class,
            RolesPermissionsSeeder::class,
        ]);
        // Usuários Básicos
        $this->call([
            AdminUserSeeder::class,
            TeacherSeeder::class,
            StudentSeeder::class
        ]);

        // Domínio Acadêmico
        $this->call([
            SchoolClassSeeder::class,
            TeacherAssignmentSeeder::class,
            EnrollmentSeeder::class,
            AttendanceSeeder::class,
            GradeSeeder::class,
            AttendanceSeeder::class,
        ]);
    }
}
