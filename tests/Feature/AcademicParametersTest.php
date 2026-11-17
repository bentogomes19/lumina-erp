<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SystemParameter;
use App\Support\AcademicSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicParametersTest extends TestCase {

    use RefreshDatabase;

    /**
     * Sem parâmetro cadastrado, a frequência mínima continua em 75%.
     *
     * @return void
     */
    public function test_minimum_attendance_defaults_to_75(): void {
        $this->assertSame(75.0, AcademicSettings::minimumAttendance());
    }

    /**
     * Valores fora de 0 a 100 são limitados.
     *
     * @return void
     */
    public function test_minimum_attendance_is_clamped(): void {
        $this->minimumAttendance('150');
        $this->assertSame(100.0, AcademicSettings::minimumAttendance());

        SystemParameter::where('key', 'academic.minimum_attendance')->update(['value' => '-10']);
        $this->assertSame(0.0, AcademicSettings::minimumAttendance());
    }

    /**
     * O alerta de frequência acompanha o mínimo configurado.
     *
     * @return void
     */
    public function test_frequency_alert_follows_parameter(): void {
        [$student, $class] = $this->studentWithFrequency(present: 7, absent: 3); // 70%

        $this->assertTrue(Attendance::calculateFrequency($student->id, $class->id)['alert']);

        $this->minimumAttendance('60');

        $this->assertFalse(Attendance::calculateFrequency($student->id, $class->id)['alert']);
    }

    /**
     * A lista de alunos em risco usa o mínimo configurado quando nenhum limite é informado.
     *
     * @return void
     */
    public function test_students_at_risk_use_parameter_by_default(): void {
        $this->minimumAttendance('80');
        [$student, $class] = $this->studentWithFrequency(present: 7, absent: 3); // 70%

        $atRisk = Attendance::getStudentsAtRisk($class->id);

        $this->assertSame([$student->id], array_values(array_column($atRisk, 'student_id')));
    }

    /**
     * A cor do indicador muda de faixa de acordo com o mínimo configurado.
     *
     * @return void
     */
    public function test_attendance_color_uses_parameter(): void {
        $this->minimumAttendance('80');

        $this->assertSame('success', AcademicSettings::attendanceColor(80.0));
        $this->assertSame('warning', AcademicSettings::attendanceColor(70.0));
        $this->assertSame('danger', AcademicSettings::attendanceColor(60.0));
    }

    /**
     * Cria um aluno matriculado com o número de presenças e faltas informado.
     *
     * @param int $present
     * @param int $absent
     *
     * @return array{0: Student, 1: SchoolClass}
     */
    private function studentWithFrequency(int $present, int $absent): array {
        $student = Student::factory()->create();
        $class   = SchoolClass::factory()->create();

        Enrollment::factory()->create(['student_id' => $student->id, 'class_id' => $class->id]);

        $statuses = array_merge(array_fill(0, $present, 'present'), array_fill(0, $absent, 'absent'));

        foreach ($statuses as $day => $status) {
            Attendance::create([
                'student_id' => $student->id,
                'class_id'   => $class->id,
                'date'       => now()->startOfYear()->addDays($day)->toDateString(),
                'status'     => $status,
            ]);
        }

        return [$student, $class];
    }

    /**
     * Cadastra o parâmetro de frequência mínima.
     *
     * @param string $value
     *
     * @return void
     */
    private function minimumAttendance(string $value): void {
        SystemParameter::create([
            'key'           => 'academic.minimum_attendance',
            'name'          => 'Frequência mínima obrigatória',
            'category'      => 'Regras acadêmicas',
            'type'          => 'decimal',
            'value'         => $value,
            'default_value' => '75.0',
            'description'   => 'Percentual mínimo de presença exigido.',
            'is_active'     => true,
            'is_system'     => true,
        ]);
    }

}
