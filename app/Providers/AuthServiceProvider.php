<?php

namespace App\Providers;

use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Policies\AdminOnlyPolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\GradePolicy;
use App\Policies\StudentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class              => UserPolicy::class,
        Role::class              => AdminOnlyPolicy::class,
        Student::class           => StudentPolicy::class,
        Teacher::class           => AdminOnlyPolicy::class,
        Subject::class           => AdminOnlyPolicy::class,
        SchoolYear::class        => AdminOnlyPolicy::class,
        SchoolClass::class       => AdminOnlyPolicy::class,
        Enrollment::class       => EnrollmentPolicy::class,
        TeacherAssignment::class => AdminOnlyPolicy::class,

        // Exemplo com regras “own/self”
        Grade::class             => GradePolicy::class,
    ];
    /**
     * Register services.
     */
    public function register(): void
    {
        //usuário ele já automáticamente ele cadastre com o nome, senha
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
