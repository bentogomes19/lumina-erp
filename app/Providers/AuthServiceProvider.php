<?php

namespace App\Providers;

use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Policies\EnrollmentPolicy;
use App\Policies\GradePolicy;
use App\Policies\GradeLevelPolicy;
use App\Policies\RolePolicy;
use App\Policies\SchoolClassPolicy;
use App\Policies\SchoolYearPolicy;
use App\Policies\StudentPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\TeacherAssignmentPolicy;
use App\Policies\TeacherPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider {

    protected $policies = [
        User::class              => UserPolicy::class,
        Role::class              => RolePolicy::class,
        Student::class           => StudentPolicy::class,
        Teacher::class           => TeacherPolicy::class,
        Subject::class           => SubjectPolicy::class,
        SchoolYear::class        => SchoolYearPolicy::class,
        SchoolClass::class       => SchoolClassPolicy::class,
        GradeLevel::class        => GradeLevelPolicy::class,
        Enrollment::class        => EnrollmentPolicy::class,
        TeacherAssignment::class => TeacherAssignmentPolicy::class,

        /* Política com escopo contextual de professor e aluno. */
        Grade::class => GradePolicy::class,
    ];
    /**
     * Registra os serviços de autenticação e autorização.
     *
     * @return void
     */
    public function register(): void {
    }

    /**
     * Inicializa as políticas e regras de autorização.
     *
     * @return void
     */
    public function boot(): void {
        $this->registerPolicies();
    }
}
