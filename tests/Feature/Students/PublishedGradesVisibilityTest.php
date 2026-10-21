<?php

namespace Tests\Feature\Students;

use App\Filament\Pages\DashboardStudent;
use App\Filament\Pages\Student\MyGrades;
use App\Filament\Pages\Student\MySubjects;
use App\Filament\Pages\Student\SubjectDetail;
use App\Filament\Widgets\StudentGradesTableWidget;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Models\Assessment;
use App\Models\Teacher;
use App\Modules\Assessments\Application\RecordTeacherGrades;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublishedGradesVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_grades_are_hidden_from_every_student_page_and_report_card(): void
    {
        [$student, $class, $enrollment] = $this->studentInActiveClass();
        $math = $this->subject($class, 'Matemática', 'MAT-VIS');
        $history = $this->subject($class, 'História', 'HIS-VIS');

        $published = $this->grade($enrollment, $math, 'b1', 6, 1, true);
        $this->grade($enrollment, $math, 'b1', 10, 1, false);
        $this->grade($enrollment, $history, 'b1', 10, 1, false);

        $this->actingAs($student->user, 'student');

        $this->assertSame([$published->id], Grade::published()->pluck('id')->all());
        $this->assertSame(6.0, (new DashboardStudent)->getPageData()['grades']['average']);
        $this->assertSame([$published->id], (new DashboardStudent)->getPageData()['recentGrades']->pluck('id')->all());

        $subjects = (new MySubjects)->getPageData();
        $this->assertSame(6.0, $subjects['stats']['overall_average']);
        $this->assertNull($subjects['subjects']->firstWhere('id', $history->id)->overall_average);

        $detail = new SubjectDetail;
        $detail->subjectId = $math->id;
        $this->assertSame([$published->id], $detail->getSubjectData()['grades']->pluck('id')->all());

        $report = (new MyGrades)->getPageData();
        $this->assertSame(6.0, $report['stats']['average']);
        $this->assertNull(collect($report['subjects'])->first(fn ($item) => $item['subject']->id === $history->id)['overall_average']);
        $this->assertSame([$published->id], collect($report['subjects'])->first(fn ($item) => $item['subject']->id === $math->id)['terms']['b1']['grades']->pluck('id')->all());

        $html = view('pdf.report-card', [
            'student' => $student,
            'currentClass' => $class->load(['schoolYear', 'gradeLevel']),
            'subjects' => $report['subjects'],
            'stats' => $report['stats'],
            'minimumGrade' => $report['min_approval'],
            'generatedAt' => now(),
        ])->render();
        $this->assertSame(1, preg_match('/<th scope="row" class="subject-name">História<\/th>(.*?)<\/tr>/s', $html, $matches));
        $this->assertStringNotContainsString('10,0', $matches[1]);
    }

    public function test_weighted_term_recovery_and_annual_average_match_across_student_pages(): void
    {
        [$student, $class, $enrollment] = $this->studentInActiveClass();
        $math = $this->subject($class, 'Matemática', 'MAT-MED');
        $portuguese = $this->subject($class, 'Português', 'POR-MED');

        $this->grade($enrollment, $math, 'b1', 4, 1, true);
        $this->grade($enrollment, $math, 'b1', 8, 3, true);
        $this->grade($enrollment, $math, 'b1', 9, 1, true, 'recovery');
        $this->grade($enrollment, $math, 'b2', 5, 1, true);
        $this->grade($enrollment, $portuguese, 'b1', 6, 1, true);
        $this->grade($enrollment, $portuguese, 'b2', 10, 1, false);

        $this->actingAs($student->user, 'student');
        Cache::forget('dashboard_student_'.$student->id);

        $report = (new MyGrades)->getPageData();
        $mathReport = collect($report['subjects'])->first(fn ($item) => $item['subject']->id === $math->id);
        $this->assertSame(7.0, $mathReport['overall_average']);
        $this->assertSame(9.0, $mathReport['terms']['b1']['final_average']);
        $this->assertSame(6.5, $report['stats']['average']);

        $this->assertSame(6.5, (new DashboardStudent)->getPageData()['grades']['average']);

        $subjects = (new MySubjects)->getPageData();
        $this->assertSame(6.5, $subjects['stats']['overall_average']);
        $mathSubject = $subjects['subjects']->firstWhere('id', $math->id);
        $this->assertSame(9.0, $mathSubject->term_averages['b1']);
        $this->assertSame(7.0, $mathSubject->overall_average);

        $detail = new SubjectDetail;
        $detail->subjectId = $math->id;
        $data = $detail->getSubjectData();
        $this->assertSame(9.0, $data['term_averages']['b1']['average']);
        $this->assertSame(7.0, $data['overall_average']);

        $widgetTerms = (new StudentGradesTableWidget)->getViewData()['gradesByTerm'];
        $this->assertSame(9.0, $widgetTerms->get('b1')->get($math->id)['average']);
    }

    public function test_teacher_publication_makes_draft_visible_and_refreshes_cached_dashboard(): void
    {
        [$student, $class, $enrollment] = $this->studentInActiveClass();
        $math = $this->subject($class, 'Matemática', 'MAT-PUB');
        $teacher = Teacher::factory()->create();
        $assessment = Assessment::factory()->create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $math->id,
            'school_year_id' => $class->school_year_id,
            'weight' => 1,
        ]);
        $recorder = app(RecordTeacherGrades::class);
        $students = collect([['student_id' => $student->id, 'enrollment_id' => $enrollment->id]]);
        $arguments = [
            'teacher' => $teacher,
            'assessment' => $assessment,
            'students' => $students,
            'gradeRows' => [$student->id => ['score' => 8, 'comment' => null]],
            'maxScore' => 10,
            'term' => 'b1',
            'assessmentType' => 'test',
            'sequence' => 1,
            'postedBy' => $teacher->user_id,
        ];

        $recorder->execute(...[...$arguments, 'publish' => false]);
        $this->actingAs($student->user, 'student');
        $this->assertNull((new DashboardStudent)->getPageData()['grades']['average']);
        $this->assertNull((new MyGrades)->getPageData()['stats']['average']);

        $recorder->execute(...[...$arguments, 'publish' => true]);
        $this->assertNotNull(Grade::query()->firstOrFail()->locked_at);
        $this->assertSame(8.0, (new DashboardStudent)->getPageData()['grades']['average']);
        $this->assertSame(8.0, (new MyGrades)->getPageData()['stats']['average']);
    }

    private function studentInActiveClass(): array
    {
        $user = User::factory()->create();
        $student = Student::factory()->create(['user_id' => $user->id]);
        $year = SchoolYear::factory()->active()->create();
        $class = SchoolClass::factory()->create(['school_year_id' => $year->id]);
        $enrollment = Enrollment::factory()->create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $year->id,
        ]);

        return [$student, $class, $enrollment];
    }

    private function subject(SchoolClass $class, string $name, string $code): Subject
    {
        $subject = Subject::factory()->create([
            'name' => $name,
            'code' => $code,
            'normalized_code' => strtolower($code),
        ]);
        $class->subjects()->attach($subject);

        return $subject;
    }

    private function grade(
        Enrollment $enrollment,
        Subject $subject,
        string $term,
        float $score,
        float $weight,
        bool $published,
        string $type = 'test',
    ): Grade {
        return Grade::factory()->create([
            'enrollment_id' => $enrollment->id,
            'student_id' => $enrollment->student_id,
            'class_id' => $enrollment->class_id,
            'subject_id' => $subject->id,
            'term' => $term,
            'assessment_type' => $type,
            'score' => $score,
            'weight' => $weight,
            'locked_at' => $published ? now() : null,
            'comment' => $published ? null : 'RASCUNHO-SECRETO',
        ]);
    }
}
