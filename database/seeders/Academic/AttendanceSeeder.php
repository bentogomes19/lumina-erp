<?php

namespace Database\Seeders\Academic;

use App\Models\Attendance;
use App\Models\Lesson;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar apenas aulas que já foram realizadas e têm chamada marcada
        $completedLessons = Lesson::where('status', 'completed')
            ->where('attendance_taken', true)
            ->with(['schoolClass.students', 'subject', 'teacher'])
            ->get();

        if ($completedLessons->isEmpty()) {
            $this->command?->warn('AttendanceSeeder: nenhuma aula com chamada encontrada. Execute LessonSeeder primeiro.');
            return;
        }

        $this->command?->info("Gerando frequências para {$completedLessons->count()} aulas...");

        $totalAttendances = 0;

        foreach ($completedLessons as $lesson) {
            $students = $lesson->schoolClass->students;

            if ($students->isEmpty()) {
                continue;
            }

            foreach ($students as $student) {
                // Simular perfil de frequência do aluno
                $attendanceProfile = $this->getStudentAttendanceProfile($student->id);
                
                // Determinar status baseado no perfil
                $status = $this->determineAttendanceStatus($attendanceProfile);

                // Calcular horário de registro (alguns minutos após início da aula)
                $recordTime = Carbon::parse($lesson->start_time)
                    ->addMinutes(rand(5, 15));

                // Se o aluno chegou atrasado, ajustar o horário
                if ($status === 'late') {
                    $recordTime = Carbon::parse($lesson->start_time)
                        ->addMinutes(rand(15, 30));
                }

                $notes = null;
                
                // Adicionar observações aleatórias
                if ($status === 'absent' && rand(1, 10) <= 3) {
                    $notes = $this->getRandomAbsenceNote();
                } elseif ($status === 'late' && rand(1, 10) <= 2) {
                    $notes = 'Chegou atrasado';
                }

                Attendance::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'lesson_id' => $lesson->id,
                    ],
                    [
                        'class_id' => $lesson->class_id,
                        'subject_id' => $lesson->subject_id,
                        'date' => $lesson->date,
                        'time' => $recordTime->format('H:i'),
                        'status' => $status,
                        'notes' => $notes,
                        'recorded_by' => $lesson->attendance_taken_by ?? 1,
                    ]
                );

                $totalAttendances++;
            }
        }

        $this->command?->info("Total de {$totalAttendances} registros de frequência gerados!");

        // Exibir estatísticas
        $this->displayStatistics();
    }

    /**
     * Obter perfil de frequência do aluno (consistência simulada)
     */
    private function getStudentAttendanceProfile(int $studentId): string
    {
        // Simular que cada aluno tem um perfil de frequência
        // Baseado no ID, manter consistência
        $seed = $studentId % 100;

        if ($seed < 70) {
            return 'excellent'; // 70% dos alunos são assíduos
        } elseif ($seed < 85) {
            return 'good'; // 15% têm boa frequência
        } elseif ($seed < 95) {
            return 'moderate'; // 10% frequência moderada
        } else {
            return 'poor'; // 5% frequência ruim (risco)
        }
    }

    /**
     * Determinar status de presença baseado no perfil
     */
    private function determineAttendanceStatus(string $profile): string
    {
        $rand = rand(1, 100);

        return match ($profile) {
            'excellent' => $rand <= 95 ? 'present' : ($rand <= 98 ? 'late' : 'absent'),
            'good' => $rand <= 85 ? 'present' : ($rand <= 92 ? 'late' : 'absent'),
            'moderate' => $rand <= 75 ? 'present' : ($rand <= 85 ? 'late' : 'absent'),
            'poor' => $rand <= 60 ? 'present' : ($rand <= 75 ? 'late' : 'absent'),
            default => 'present',
        };
    }

    /**
     * Obter nota aleatória de justificativa de falta
     */
    private function getRandomAbsenceNote(): ?string
    {
        $notes = [
            'Atestado médico',
            'Falta justificada pelos responsáveis',
            'Compromisso familiar',
            'Consulta médica',
            null, // Maioria não tem observação
            null,
            null,
            null,
        ];

        return $notes[array_rand($notes)];
    }

    /**
     * Exibir estatísticas de frequência
     */
    private function displayStatistics(): void
    {
        $this->command?->info("\n=== Estatísticas de Frequência ===");

        $total = Attendance::count();
        $present = Attendance::where('status', 'present')->count();
        $late = Attendance::where('status', 'late')->count();
        $absent = Attendance::where('status', 'absent')->count();

        $presentPercentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;
        $latePercentage = $total > 0 ? round(($late / $total) * 100, 2) : 0;
        $absentPercentage = $total > 0 ? round(($absent / $total) * 100, 2) : 0;

        $this->command?->info("Total: {$total}");
        $this->command?->info("Presentes: {$present} ({$presentPercentage}%)");
        $this->command?->info("Atrasados: {$late} ({$latePercentage}%)");
        $this->command?->info("Ausentes: {$absent} ({$absentPercentage}%)");

        // Alunos em risco (frequência < 75%)
        $studentsAtRisk = $this->getStudentsAtRisk();
        
        if (!empty($studentsAtRisk)) {
            $this->command?->warn("\n⚠️ Alunos em risco de reprovação por falta (< 75%):");
            foreach ($studentsAtRisk as $risk) {
                $this->command?->warn(sprintf(
                    "  - %s: %.2f%% de frequência (%d/%d presenças)",
                    $risk['student_name'],
                    $risk['frequency'],
                    $risk['present'] + $risk['late'],
                    $risk['total']
                ));
            }
        }
    }

    /**
     * Identificar alunos em risco de reprovação por falta
     */
    private function getStudentsAtRisk(): array
    {
        $students = Student::with('classes')->get();
        $atRisk = [];

        foreach ($students as $student) {
            $total = Attendance::where('student_id', $student->id)->count();

            if ($total === 0) {
                continue;
            }

            $present = Attendance::where('student_id', $student->id)
                ->where('status', 'present')
                ->count();
            
            $late = Attendance::where('student_id', $student->id)
                ->where('status', 'late')
                ->count();

            $frequency = (($present + $late) / $total) * 100;

            if ($frequency < 75.0) {
                $atRisk[] = [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'frequency' => round($frequency, 2),
                    'present' => $present,
                    'late' => $late,
                    'total' => $total,
                ];
            }
        }

        return $atRisk;
    }
}
