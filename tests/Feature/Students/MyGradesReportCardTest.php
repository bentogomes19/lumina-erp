<?php

namespace Tests\Feature\Students;

use App\Filament\Pages\Student\MyGrades;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MyGradesReportCardTest extends TestCase {
    use RefreshDatabase;

    public function test_annual_report_shows_only_current_students_subjects_and_calculates_recovery(): void {
        [$student, $class, $enrollment] = $this->studentInActiveClass();
        $math = Subject::factory()->create(['name' => 'Matemática', 'code' => 'MAT-T', 'normalized_code' => 'mat-t']);
        $history = Subject::factory()->create(['name' => 'História', 'code' => 'HIS-T', 'normalized_code' => 'his-t']);
        $class->subjects()->attach([$math->id, $history->id]);

        $this->grade($enrollment, $math, 'b1', 4);
        $this->grade($enrollment, $math, 'b2', 8);
        $this->grade($enrollment, $math, 'b1', 7, 'recovery');

        $other = Student::factory()->create();
        $otherEnrollment = Enrollment::factory()->create([
            'student_id' => $other->id,
            'class_id' => $class->id,
            'school_year_id' => $class->school_year_id,
        ]);
        $this->grade($otherEnrollment, $math, 'b3', 10);

        $this->actingAs($student->user, 'student');
        $page = new MyGrades();
        $this->assertSame('all', $page->selectedPeriod);
        $data = $page->getPageData();
        $this->assertSame(2, $data['stats']['total']);

        $mathReport = collect($data['subjects'])->first(fn ($item) => $item['subject']->id === $math->id);
        $historyReport = collect($data['subjects'])->first(fn ($item) => $item['subject']->id === $history->id);
        $this->assertSame(7.0, $mathReport['terms']['b1']['final_average']);
        $this->assertSame(8.0, $mathReport['terms']['b2']['final_average']);
        $this->assertNull($mathReport['terms']['b3']['final_average']);
        $this->assertSame(7.5, $mathReport['overall_average']);
        $this->assertSame('1º: 7,0', MyGrades::recoverySummary($mathReport));
        $this->assertNull($historyReport['overall_average']);
        $this->assertSame('—', MyGrades::recoverySummary($historyReport));
    }

    public function test_pdf_has_one_compact_column_per_term_and_annual_data_when_screen_is_filtered(): void {
        [$student, $class, $enrollment] = $this->studentInActiveClass();
        $subject = Subject::factory()->create(['name' => 'Matemática', 'code' => 'MAT-P', 'normalized_code' => 'mat-p']);
        $class->subjects()->attach($subject);
        $this->grade($enrollment, $subject, 'b1', 5);
        $this->grade($enrollment, $subject, 'b2', 9);
        $this->grade($enrollment, $subject, 'b1', 7, 'recovery');

        $this->actingAs($student->user, 'student');
        $page = new MyGrades();
        $page->selectedPeriod = 'b1';
        $this->assertSame(7.0, $page->getPageData()['subjects'][0]['overall_average']);

        $method = new \ReflectionMethod(MyGrades::class, 'buildPageData');
        $annual = $method->invoke($page, 'all');
        $this->assertSame(8.0, $annual['subjects'][0]['overall_average']);

        $viewData = [
            'student' => $student,
            'currentClass' => $class->load(['schoolYear', 'gradeLevel']),
            'subjects' => $annual['subjects'],
            'stats' => $annual['stats'],
            'minimumGrade' => $annual['min_approval'],
            'generatedAt' => now(),
        ];
        $html = view('pdf.report-card', $viewData)->render();

        $this->assertSame(1, substr_count($html, '1º bimestre</th>'));
        $this->assertSame(1, substr_count($html, '2º bimestre</th>'));
        $this->assertSame(1, substr_count($html, '3º bimestre</th>'));
        $this->assertSame(1, substr_count($html, '4º bimestre</th>'));
        $this->assertStringContainsString('Média<br>final*', $html);
        $this->assertStringContainsString('Recuperação</th>', $html);
        $this->assertStringContainsString('1º: 7,0', $html);
        $this->assertStringContainsString('8,0', $html);
        $this->assertStringStartsWith('%PDF-', Pdf::loadView('pdf.report-card', $viewData)->output());
    }

    public function test_student_page_renders_annual_columns_and_keeps_term_filter_available(): void {
        [$student, $class, $enrollment] = $this->studentInActiveClass();
        $student->user->assignRole(Role::findOrCreate('student', 'web'));
        $student->user->givePermissionTo(Permission::findOrCreate('student.grades.view', 'web'));
        $subject = Subject::factory()->create(['name' => 'Matemática', 'code' => 'MAT-I', 'normalized_code' => 'mat-i']);
        $class->subjects()->attach($subject);
        $this->grade($enrollment, $subject, 'b1', 7);

        $this->actingAs($student->user, 'student');
        $this->assertSame(
            Filament::getPanel('lumina')->getColors()['primary'],
            Filament::getPanel('aluno')->getColors()['primary'],
        );
        Livewire::test(MyGrades::class)
            ->assertSuccessful()
            ->assertSet('selectedPeriod', 'all')
            ->assertSee('Meu boletim')
            ->assertSee('1º bimestre')
            ->assertSee('4º bimestre')
            ->assertSee('Média final')
            ->assertSee('Recuperação')
            ->assertDontSee('Média do período')
            ->assertDontSee('Precisam de atenção')
            ->assertDontSee('fi-wi-stats-overview')
            ->call('setPeriod', 'b1')
            ->assertSet('selectedPeriod', 'b1')
            ->assertSee('Boletim do 1º bimestre')
            ->assertSee('Prova 1')
            ->assertSee('Média final')
            ->assertDontSee('Buscar disciplina');
    }

    public function test_bimester_table_shows_every_assessment_recovery_and_missing_scores(): void {
        [$student, $class, $enrollment] = $this->studentInActiveClass();
        $student->user->assignRole(Role::findOrCreate('student', 'web'));
        $student->user->givePermissionTo(Permission::findOrCreate('student.grades.view', 'web'));
        $science = Subject::factory()->create(['name' => 'Ciências Naturais', 'code' => 'CI-N', 'normalized_code' => 'ci-n']);
        $math = Subject::factory()->create(['name' => 'Matemática', 'code' => 'MAT-B', 'normalized_code' => 'mat-b']);
        $portuguese = Subject::factory()->create(['name' => 'Português', 'code' => 'PT-B', 'normalized_code' => 'pt-b']);
        $class->subjects()->attach([$science->id, $math->id, $portuguese->id]);

        $this->grade($enrollment, $science, 'b1', 6.5, 'test', 1);
        $this->grade($enrollment, $science, 'b1', 7.8, 'test', 2);
        $this->grade($enrollment, $science, 'b1', 8, 'test', 3);
        $this->grade($enrollment, $science, 'b1', 9, 'recovery');
        $this->grade($enrollment, $math, 'b1', 7.2, 'test', 2);
        $this->grade($enrollment, $math, 'b1', 8, 'work', 1);

        $this->actingAs($student->user, 'student');
        $page = new MyGrades();
        $page->selectedPeriod = 'b1';
        $data = $page->getPageData();
        $this->assertSame(['Prova 1', 'Prova 2', 'Prova 3', 'Trabalho 1'], array_column($data['assessment_columns'], 'label'));
        $this->assertSame(3, $data['stats']['total']);
        $this->assertNull(collect($data['subjects'])->first(fn ($item) => $item['subject']->id === $portuguese->id)['overall_average']);
        $mathReport = collect($data['subjects'])->first(fn ($item) => $item['subject']->id === $math->id);
        $scores = (new \ReflectionMethod(MyGrades::class, 'assessmentScoresForPeriod'))->invoke($page, $mathReport, 'b1');
        $this->assertArrayNotHasKey('test_1', $scores);
        $this->assertEquals(7.2, $scores['test_2']);

        Livewire::test(MyGrades::class)
            ->call('setPeriod', 'b1')
            ->assertSee('Prova 1')
            ->assertSee('Prova 2')
            ->assertSee('Prova 3')
            ->assertSee('Trabalho 1')
            ->assertSee('Recuperação')
            ->assertSee('Média final')
            ->assertSee('6,5')
            ->assertSee('7,8')
            ->assertSee('9,0')
            ->assertDontSee('Buscar disciplina');
    }

    public function test_fourth_bimester_without_grades_keeps_two_provas_recovery_and_final_average(): void {
        [$student, $class, $enrollment] = $this->studentInActiveClass();
        $student->user->assignRole(Role::findOrCreate('student', 'web'));
        $student->user->givePermissionTo(Permission::findOrCreate('student.grades.view', 'web'));
        $math = Subject::factory()->create(['name' => 'Matemática', 'code' => 'MAT-4', 'normalized_code' => 'mat-4']);
        $science = Subject::factory()->create(['name' => 'Ciências', 'code' => 'CI-4', 'normalized_code' => 'ci-4']);
        $class->subjects()->attach([$math->id, $science->id]);
        $this->grade($enrollment, $math, 'b1', 8);

        $this->actingAs($student->user, 'student');
        $page = new MyGrades();
        $page->selectedPeriod = 'b4';
        $data = $page->getPageData();
        $this->assertSame(['Prova 1', 'Prova 2'], array_column($data['assessment_columns'], 'label'));
        $this->assertSame(2, $data['stats']['ongoing']);
        foreach ($data['subjects'] as $item) {
            $this->assertNull($item['terms']['b4']['final_average']);
            $this->assertNull($item['terms']['b4']['recovery']);
        }

        Livewire::test(MyGrades::class)
            ->call('setPeriod', 'b4')
            ->assertSee('Boletim do 4º bimestre')
            ->assertSee('Prova 1')
            ->assertSee('Prova 2')
            ->assertSee('Recuperação')
            ->assertSee('Média final')
            ->assertSee('—');
    }

    public function test_report_cards_and_pdf_keep_subjects_in_alphabetical_order(): void {
        [$student, $class, $enrollment] = $this->studentInActiveClass();
        $math = Subject::factory()->create(['name' => 'Matemática', 'code' => 'MAT-O', 'normalized_code' => 'mat-o']);
        $science = Subject::factory()->create(['name' => 'Ciências', 'code' => 'CIE-O', 'normalized_code' => 'cie-o']);
        $algebra = Subject::factory()->create(['name' => 'Álgebra', 'code' => 'ALG-O', 'normalized_code' => 'alg-o']);
        $class->subjects()->attach([$math->id, $science->id, $algebra->id]);
        $this->grade($enrollment, $math, 'b1', 3);
        $this->grade($enrollment, $algebra, 'b1', 9);

        $student->user->assignRole(Role::findOrCreate('student', 'web'));
        $student->user->givePermissionTo(Permission::findOrCreate('student.grades.view', 'web'));
        $this->actingAs($student->user, 'student');
        $page = new MyGrades();
        $expected = ['Álgebra', 'Ciências', 'Matemática'];
        $annual = $page->getPageData();
        $this->assertSame($expected, collect($annual['subjects'])->pluck('subject.name')->all());

        foreach (['b1', 'b4'] as $period) {
            $page->selectedPeriod = $period;
            $this->assertSame($expected, collect($page->getPageData()['subjects'])->pluck('subject.name')->all());
        }

        $livewire = Livewire::test(MyGrades::class);
        foreach ([$livewire->html(), $livewire->call('setPeriod', 'b1')->html()] as $rendered) {
            $positions = array_map(fn (string $name) => strpos($rendered, $name), $expected);
            $this->assertNotContains(false, $positions);
            $this->assertSame($positions, collect($positions)->sort()->values()->all());
        }

        $html = view('pdf.report-card', [
            'student' => $student,
            'currentClass' => $class->load(['schoolYear', 'gradeLevel']),
            'subjects' => $annual['subjects'],
            'stats' => $annual['stats'],
            'minimumGrade' => $annual['min_approval'],
            'generatedAt' => now(),
        ])->render();
        $this->assertLessThan(strpos($html, 'Ciências'), strpos($html, 'Álgebra'));
        $this->assertLessThan(strpos($html, 'Matemática'), strpos($html, 'Ciências'));
    }

    private function studentInActiveClass(): array {
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

    private function grade(Enrollment $enrollment, Subject $subject, string $term, float $score, string $type = 'test', int $sequence = 1): void {
        Grade::factory()->create([
            'enrollment_id' => $enrollment->id,
            'student_id' => $enrollment->student_id,
            'class_id' => $enrollment->class_id,
            'subject_id' => $subject->id,
            'term' => $term,
            'assessment_type' => $type,
            'score' => $score,
            'sequence' => $sequence,
            'weight' => 1,
        ]);
    }
}
