<?php

namespace Tests\Feature\Pdf;

use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\Core\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * A média da declaração de transferência externa deve ser a mesma do boletim:
 * só notas publicadas, ponderadas pelo peso e calculadas por bimestre.
 */
class TransferDeclarationAverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_average_matches_boletim_rules(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $year = SchoolYear::factory()->create(['is_active' => true, 'status' => 'ativo']);
        $class = SchoolClass::factory()->create(['school_year_id' => $year->id]);
        $enrollment = Enrollment::factory()->create(['class_id' => $class->id, 'school_year_id' => $year->id]);
        $subject = Subject::factory()->create(['name' => 'Matemática']);

        /* 1º bimestre: prova 10 (peso 2) e trabalho 4 (peso 1) → (20 + 4) / 3 = 8,0. */
        $this->grade($enrollment, $subject, 'b1', 'test', 10, 2, published: true);
        $this->grade($enrollment, $subject, 'b1', 'work', 4, 1, published: true);
        /* Rascunho do professor: não pode entrar na média. */
        $this->grade($enrollment, $subject, 'b2', 'test', 0, 2, published: false);

        $captured = null;
        View::composer('pdf.enrollment.transferencia-externa', function ($view) use (&$captured): void {
            $captured = $view->getData()['historicoNotas'];
        });

        $this->actingAs($admin)
            ->get(route('pdf.enrollment.transferencia-externa', $enrollment))
            ->assertOk();

        $this->assertNotNull($captured);
        $this->assertCount(1, $captured);
        $this->assertEqualsWithDelta(8.0, $captured['Matemática']['media'], 0.001);
    }

    private function grade(Enrollment $enrollment, Subject $subject, string $term, string $type, float $score, int $weight, bool $published): Grade
    {
        return Grade::factory()->create([
            'enrollment_id' => $enrollment->id,
            'subject_id' => $subject->id,
            'term' => $term,
            'assessment_type' => $type,
            'score' => $score,
            'weight' => $weight,
            'locked_at' => $published ? now() : null,
        ]);
    }
}
