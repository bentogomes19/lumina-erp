<?php

namespace App\Http\Controllers\Examples;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Lesson;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

/**
 * Exemplos de uso do sistema de frequências
 * 
 * Este controller demonstra casos de uso comuns do sistema
 */
class FrequenciaExampleController
{
    /**
     * Exemplo 1: Lançar frequência de uma aula
     */
    public function lancarChamada(int $lessonId): JsonResponse
    {
        $lesson = Lesson::findOrFail($lessonId);

        // Verificar se pode lançar chamada
        if (!$lesson->canTakeAttendance(maxDaysAfter: 3)) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível lançar chamada. Prazo expirado ou aula cancelada.',
            ], 422);
        }

        // Obter alunos da turma
        $students = $lesson->schoolClass->students;

        $registros = [];

        foreach ($students as $student) {
            // Simular que 90% estão presentes
            $status = rand(1, 10) <= 9 
                ? AttendanceStatus::PRESENT 
                : AttendanceStatus::ABSENT;

            $attendance = Attendance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'lesson_id' => $lesson->id,
                ],
                [
                    'class_id' => $lesson->class_id,
                    'subject_id' => $lesson->subject_id,
                    'date' => $lesson->date,
                    'time' => now()->format('H:i'),
                    'status' => $status,
                    'recorded_by' => auth()->id(),
                ]
            );

            $registros[] = [
                'student' => $student->name,
                'status' => $status->label(),
            ];
        }

        // Marcar aula como chamada realizada
        $lesson->markAttendanceTaken(auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Chamada lançada com sucesso!',
            'registros' => $registros,
            'taxa_presenca' => $lesson->getAttendanceRate(),
        ]);
    }

    /**
     * Exemplo 2: Consultar frequência de um aluno
     */
    public function consultarFrequencia(int $studentId, int $classId): JsonResponse
    {
        $student = Student::findOrFail($studentId);

        // Frequência geral do aluno na turma
        $stats = Attendance::calculateFrequency(
            studentId: $studentId,
            classId: $classId,
            startDate: now()->startOfYear(),
            endDate: now()
        );

        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'registration' => $student->registration_number,
            ],
            'frequencia' => $stats,
            'em_risco' => $stats['alert'],
            'mensagem' => $stats['alert'] 
                ? '⚠️ Aluno em risco de reprovação por faltas!' 
                : '✓ Frequência adequada',
        ]);
    }

    /**
     * Exemplo 3: Relatório de frequência da turma
     */
    public function relatorioTurma(int $classId): JsonResponse
    {
        $report = Attendance::getClassFrequencyReport(
            classId: $classId,
            startDate: now()->startOfMonth(),
            endDate: now()
        );

        // Separar alunos em risco
        $total = count($report);
        $emRisco = array_filter($report, fn($item) => $item['alert']);
        $countEmRisco = count($emRisco);

        // Calcular média da turma
        $mediaFrequencia = $total > 0 
            ? array_sum(array_column($report, 'frequency')) / $total 
            : 0;

        return response()->json([
            'turma_id' => $classId,
            'periodo' => now()->format('m/Y'),
            'total_alunos' => $total,
            'media_frequencia' => round($mediaFrequencia, 2),
            'alunos_em_risco' => $countEmRisco,
            'percentual_risco' => $total > 0 ? round(($countEmRisco / $total) * 100, 2) : 0,
            'alunos' => $report,
        ]);
    }

    /**
     * Exemplo 4: Identificar alunos em risco
     */
    public function alunosEmRisco(int $classId): JsonResponse
    {
        $atRisk = Attendance::getStudentsAtRisk(
            classId: $classId,
            thresholdPercentage: 75.0
        );

        $detalhes = [];

        foreach ($atRisk as $aluno) {
            $faltasRestantes = $this->calcularFaltasRestantes(
                $aluno['total'],
                $aluno['present'] + $aluno['late']
            );

            $detalhes[] = [
                'aluno' => $aluno['student_name'],
                'frequencia_atual' => $aluno['frequency'] . '%',
                'presenças' => $aluno['present'],
                'atrasos' => $aluno['late'],
                'faltas' => $aluno['absent'],
                'total_aulas' => $aluno['total'],
                'faltas_restantes_permitidas' => $faltasRestantes,
                'acao_recomendada' => $faltasRestantes <= 2 
                    ? 'CRÍTICO: Convocar responsáveis imediatamente'
                    : 'Monitorar de perto e notificar responsáveis',
            ];
        }

        return response()->json([
            'total_em_risco' => count($atRisk),
            'alerta' => count($atRisk) > 0 
                ? '⚠️ Existem alunos em risco de reprovação por faltas!' 
                : '✓ Nenhum aluno em risco no momento',
            'detalhes' => $detalhes,
        ]);
    }

    /**
     * Exemplo 5: Dashboard de frequências
     */
    public function dashboard(): JsonResponse
    {
        // Estatísticas gerais do dia
        $hoje = today();
        
        $aulasHoje = Lesson::onDate($hoje)->count();
        $aulasChamadaFeita = Lesson::onDate($hoje)
            ->attendanceTaken()
            ->count();
        
        $aulasPendentes = Lesson::attendancePending()
            ->where('date', '<', now())
            ->count();

        // Frequência geral do mês
        $inicio = now()->startOfMonth();
        $fim = now();
        
        $totalPresencas = Attendance::dateRange($inicio, $fim)
            ->present()
            ->count();
        
        $totalRegistros = Attendance::dateRange($inicio, $fim)->count();
        
        $taxaPresencaMes = $totalRegistros > 0 
            ? round(($totalPresencas / $totalRegistros) * 100, 2) 
            : 0;

        return response()->json([
            'hoje' => [
                'aulas_total' => $aulasHoje,
                'chamadas_realizadas' => $aulasChamadaFeita,
                'percentual_completo' => $aulasHoje > 0 
                    ? round(($aulasChamadaFeita / $aulasHoje) * 100, 2) 
                    : 0,
            ],
            'pendencias' => [
                'aulas_sem_chamada' => $aulasPendentes,
                'alerta' => $aulasPendentes > 0 
                    ? '⚠️ Existem aulas passadas sem chamada registrada' 
                    : '✓ Todas as chamadas estão em dia',
            ],
            'mes_atual' => [
                'periodo' => $inicio->format('m/Y'),
                'total_registros' => $totalRegistros,
                'total_presencas' => $totalPresencas,
                'taxa_presenca' => $taxaPresencaMes . '%',
            ],
        ]);
    }

    /**
     * Calcular quantas faltas o aluno ainda pode ter sem reprovar
     */
    private function calcularFaltasRestantes(int $totalAulas, int $presencas): int
    {
        // Para manter 75% de frequência, o aluno pode faltar no máximo 25%
        $maxFaltas = floor($totalAulas * 0.25);
        $faltasAtuais = $totalAulas - $presencas;
        $restantes = $maxFaltas - $faltasAtuais;
        
        return max(0, (int) $restantes);
    }
}
