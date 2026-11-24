<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Throwable;

/**
 * Varre as tabelas administrativas: abre cada uma, busca e ordena todas as
 * colunas ordenáveis. Pega erros de SQL como colunas ambíguas em joins.
 */
class ScreenSmokeTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $failures = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['population.profile' => 'pequena']);
        $this->seed(DatabaseSeeder::class);
    }

    /* Um único teste para semear o banco uma vez só (a carga leva cerca de um minuto). */
    public function test_admin_teacher_and_student_screens_load_search_sort_and_filter(): void
    {
        $this->sweepAdminPanel();
        $this->sweepTeacherPanel();
        $this->sweepStudentPanel();

        $this->assertSame([], $this->failures, implode("\n\n", $this->failures));
    }

    private function sweepAdminPanel(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('lumina'));
        $this->actingAs(User::query()->where('email', 'admin@lumina.com')->firstOrFail());

        foreach ([
            \App\Filament\Pages\DashboardAdmin::class,
            \App\Filament\Pages\Admin\PermissionMatrix::class,
            \App\Filament\Widgets\AdminEnrollmentStatusChart::class,
            \App\Filament\Widgets\AdminEnrollmentTrendChart::class,
            \App\Filament\Widgets\AdminOverviewStats::class,
            \App\Filament\Widgets\AdminQuickAccess::class,
            \App\Filament\Widgets\AdminRecentEnrollmentsTable::class,
            \App\Filament\Widgets\AdminSchoolClassesTable::class,
            \App\Filament\Widgets\AdminStrategicStats::class,
            \App\Filament\Widgets\EnrollmentStatsWidget::class,
        ] as $component) {
            $this->exercise($component, []);
        }

        $listPages = [
            Resources\Enrollments\Pages\ListEnrollments::class,
            Resources\GradeLevels\Pages\ListGradeLevels::class,
            Resources\Roles\Pages\ListRoles::class,
            Resources\SchoolClasses\Pages\ListSchoolClasses::class,
            Resources\SchoolYears\Pages\ListSchoolYears::class,
            Resources\Students\Pages\ListStudents::class,
            Resources\Subjects\Pages\ListSubjects::class,
            Resources\SystemParameters\Pages\ListSystemParameters::class,
            Resources\TeacherAssignments\Pages\ListTeacherAssignments::class,
            Resources\Teachers\Pages\ListTeachers::class,
            Resources\Users\Pages\ListUsers::class,
        ];

        foreach ($listPages as $page) {
            $this->exercise($page, []);
        }

        $relationManagers = [
            [Resources\Enrollments\RelationManagers\EnrollmentLogsRelationManager::class, Resources\Enrollments\Pages\EditEnrollment::class, \App\Models\Enrollment::class],
            [Resources\Enrollments\RelationManagers\EnrollmentDocumentsRelationManager::class, Resources\Enrollments\Pages\EditEnrollment::class, \App\Models\Enrollment::class],
            [Resources\GradeLevels\RelationManagers\SubjectsRelationManager::class, Resources\GradeLevels\Pages\EditGradeLevel::class, \App\Models\GradeLevel::class],
            [Resources\SchoolClasses\RelationManagers\StudentsRelationManager::class, Resources\SchoolClasses\Pages\EditSchoolClass::class, \App\Models\SchoolClass::class],
            [Resources\SchoolClasses\RelationManagers\SubjectsRelationManager::class, Resources\SchoolClasses\Pages\EditSchoolClass::class, \App\Models\SchoolClass::class],
            [Resources\SchoolYears\RelationManagers\TermsRelationManager::class, Resources\SchoolYears\Pages\EditSchoolYear::class, \App\Models\SchoolYear::class],
            [Resources\Students\RelationManagers\EnrollmentsRelationManager::class, Resources\Students\Pages\EditStudent::class, \App\Models\Student::class],
            [Resources\Teachers\RelationManager\AssignmentsRelationManager::class, Resources\Teachers\Pages\EditTeacher::class, \App\Models\Teacher::class],
        ];

        foreach ($relationManagers as [$manager, $page, $model]) {
            /** @var Model $owner */
            $owner = $model::query()->firstOrFail();
            $this->exercise($manager, ['ownerRecord' => $owner, 'pageClass' => $page]);
        }
    }

    private function sweepTeacherPanel(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('professor'));
        $this->actingAs(User::query()->where('email', 'professor@lumina.com')->firstOrFail());

        foreach ([
            \App\Filament\Pages\Teacher\DashboardTeacher::class,
            \App\Filament\Pages\Teacher\MyClasses::class,
            \App\Filament\Pages\Teacher\TeacherAssessments::class,
            \App\Filament\Pages\Teacher\TeacherAttendance::class,
            \App\Filament\Pages\Teacher\TeacherGrades::class,
            \App\Filament\Pages\Teacher\TeacherProfile::class,
            \App\Filament\Pages\Teacher\TeacherSchedule::class,
            \App\Filament\Widgets\MyClassesTable::class,
            \App\Filament\Widgets\RecentAttendanceTeacher::class,
            \App\Filament\Widgets\TeacherAttendanceWidget::class,
            \App\Filament\Widgets\TeacherStats::class,
        ] as $component) {
            $this->exercise($component, [], 'professor');
        }
    }

    private function sweepStudentPanel(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('aluno'));
        $user = User::query()->where('email', 'aluno@lumina.com')->firstOrFail();
        $this->actingAs($user);

        foreach ([
            \App\Filament\Pages\DashboardStudent::class,
            \App\Filament\Pages\Student\AcademicCalendar::class,
            \App\Filament\Pages\Student\MyGrades::class,
            \App\Filament\Pages\Student\MySubjects::class,
            \App\Filament\Pages\Student\StudentAttendance::class,
            \App\Filament\Widgets\StudentAttendanceStatsWidget::class,
            \App\Filament\Widgets\StudentGradesOverviewWidget::class,
            \App\Filament\Widgets\StudentGradesStatsWidget::class,
            \App\Filament\Widgets\StudentGradesTableWidget::class,
            \App\Filament\Widgets\StudentGradesWidget::class,
            \App\Filament\Widgets\StudentProfileWidget::class,
            \App\Filament\Widgets\UpcomingAssessments::class,
        ] as $component) {
            $this->exercise($component, [], 'aluno');
        }

        /* Detalhe de cada disciplina da turma atual do aluno. */
        $classId = \App\Models\Enrollment::query()
            ->where('student_id', \App\Models\Student::query()->where('user_id', $user->id)->value('id'))
            ->where('status', 'Ativa')
            ->latest('id')
            ->value('class_id');
        $subjectIds = \Illuminate\Support\Facades\DB::table('class_subjects')->where('class_id', $classId)->pluck('subject_id');

        if ($subjectIds->isEmpty()) {
            $this->failures[] = 'SubjectDetail: aluno@lumina.com sem disciplinas na turma ativa (seed não cobre a tela).';
        }

        foreach ($subjectIds as $subjectId) {
            $this->exercise(\App\Filament\Pages\Student\SubjectDetail::class, ['subject' => $subjectId], 'aluno');
        }
    }

    /**
     * @param class-string $component
     * @param array<string, mixed> $params
     */
    private function exercise(string $component, array $params, string $panel = 'lumina'): void
    {
        $label = $panel.' › '.class_basename($component);

        try {
            $test = Livewire::test($component, $params)->assertSuccessful();
        } catch (Throwable $e) {
            $this->failures[] = "{$label} [abrir]: ".$this->describe($e);

            return;
        }

        if (! $test->instance() instanceof \Filament\Tables\Contracts\HasTable) {
            return;
        }

        /** @var array<string, Column> $columns */
        $columns = $test->instance()->getTable()->getColumns();

        $this->exerciseFilters($component, $params, $label, $test->instance()->getTable());

        foreach (['a', 'Maria', '2026', 'ALU'] as $term) {
            try {
                Livewire::test($component, $params)->searchTable($term)->assertSuccessful();
            } catch (Throwable $e) {
                $this->failures[] = "{$label} [busca '{$term}']: ".$this->describe($e);
                break;
            }
        }

        foreach ($columns as $name => $column) {
            if (! $column->isSortable()) {
                continue;
            }

            foreach (['asc', 'desc'] as $direction) {
                try {
                    Livewire::test($component, $params)->sortTable($name, $direction)->assertSuccessful();
                } catch (Throwable $e) {
                    $this->failures[] = "{$label} [ordenar {$name} {$direction}]: ".$this->describe($e);
                    break;
                }
            }
        }
    }

    /**
     * @param class-string $component
     * @param array<string, mixed> $params
     */
    private function exerciseFilters(string $component, array $params, string $label, $table): void
    {
        foreach ($table->getFilters() as $name => $filter) {
            $value = null;

            if ($filter instanceof SelectFilter) {
                $options = $filter->getOptions();
                $first = array_key_first($options);
                if ($first === null) {
                    continue;
                }
                $value = is_array($options[$first]) ? array_key_first($options[$first]) : $first;
                if ($filter->isMultiple()) {
                    $value = [$value];
                }
            } elseif ($filter instanceof TernaryFilter) {
                $value = true;
            } else {
                continue;
            }

            try {
                Livewire::test($component, $params)->filterTable($name, $value)->assertSuccessful();
            } catch (Throwable $e) {
                $this->failures[] = "{$label} [filtro {$name}]: ".$this->describe($e);
            }
        }
    }

    private function describe(Throwable $e): string
    {
        while ($e->getPrevious() !== null) {
            $e = $e->getPrevious();
        }

        return get_class($e).': '.mb_substr($e->getMessage(), 0, 400);
    }
}
