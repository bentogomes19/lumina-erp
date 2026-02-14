<?php

namespace App\Http\Controllers\Examples;

use App\Models\SchoolHoliday;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

/**
 * Exemplos de uso do sistema de dias letivos
 */
class DiasLetivosExampleController
{
    /**
     * Exemplo 1: Verificar se uma data é dia letivo
     */
    public function verificarDiaLetivo(string $date): JsonResponse
    {
        $targetDate = Carbon::parse($date);
        
        $isSchoolDay = SchoolHoliday::isSchoolDay($targetDate);
        
        // Se não é dia letivo, buscar o motivo
        $reason = null;
        if (!$isSchoolDay) {
            if ($targetDate->isWeekend()) {
                $reason = $targetDate->isSaturday() ? 'Sábado' : 'Domingo';
            } else {
                $holiday = SchoolHoliday::active()
                    ->where('start_date', '<=', $targetDate)
                    ->where('end_date', '>=', $targetDate)
                    ->first();
                
                $reason = $holiday ? $holiday->name . ' (' . $holiday->type->label() . ')' : 'Desconhecido';
            }
        }
        
        return response()->json([
            'data' => $targetDate->format('d/m/Y'),
            'dia_semana' => $targetDate->locale('pt_BR')->dayName,
            'dia_letivo' => $isSchoolDay,
            'motivo_nao_letivo' => $reason,
        ]);
    }

    /**
     * Exemplo 2: Obter próximos feriados
     */
    public function proximosFeriados(): JsonResponse
    {
        $upcoming = SchoolHoliday::getUpcoming(10);
        
        $holidays = $upcoming->map(function ($holiday) {
            return [
                'nome' => $holiday->name,
                'tipo' => $holiday->type->label(),
                'periodo' => $holiday->getFormattedPeriod(),
                'duracao_dias' => $holiday->getDurationInDays(),
                'descricao' => $holiday->description,
                'dias_ate' => now()->diffInDays($holiday->start_date),
            ];
        });
        
        return response()->json([
            'total' => $upcoming->count(),
            'proximo' => $upcoming->first()?->name,
            'feriados' => $holidays,
        ]);
    }

    /**
     * Exemplo 3: Contar dias letivos no mês
     */
    public function diasLetivosNoMes(int $month, int $year): JsonResponse
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        $totalDays = $startDate->daysInMonth;
        $schoolDays = SchoolHoliday::countSchoolDaysInPeriod($startDate, $endDate);
        $nonSchoolDays = $totalDays - $schoolDays;
        
        // Obter lista de dias não letivos
        $nonSchoolDaysList = SchoolHoliday::getNonSchoolDaysInPeriod($startDate, $endDate);
        
        // Agrupar por motivo
        $breakdown = [
            'fins_de_semana' => 0,
            'feriados' => [],
        ];
        
        foreach ($nonSchoolDaysList as $date) {
            $d = Carbon::parse($date);
            
            if ($d->isWeekend()) {
                $breakdown['fins_de_semana']++;
            } else {
                $holiday = SchoolHoliday::active()
                    ->where('start_date', '<=', $d)
                    ->where('end_date', '>=', $d)
                    ->first();
                
                if ($holiday) {
                    $breakdown['feriados'][] = [
                        'data' => $d->format('d/m/Y'),
                        'nome' => $holiday->name,
                        'tipo' => $holiday->type->label(),
                    ];
                }
            }
        }
        
        return response()->json([
            'mes' => $startDate->locale('pt_BR')->monthName,
            'ano' => $year,
            'total_dias' => $totalDays,
            'dias_letivos' => $schoolDays,
            'dias_nao_letivos' => $nonSchoolDays,
            'detalhamento' => [
                'fins_de_semana' => $breakdown['fins_de_semana'],
                'feriados_recessos' => count($breakdown['feriados']),
                'lista_feriados' => $breakdown['feriados'],
            ],
            'percentual_letivo' => round(($schoolDays / $totalDays) * 100, 2) . '%',
        ]);
    }

    /**
     * Exemplo 4: Calendário do ano letivo
     */
    public function calendarioAnoLetivo(): JsonResponse
    {
        $schoolYear = \App\Models\SchoolYear::current();
        
        if (!$schoolYear) {
            return response()->json([
                'error' => 'Nenhum ano letivo ativo encontrado',
            ], 404);
        }
        
        $startDate = $schoolYear->starts_at;
        $endDate = $schoolYear->ends_at;
        
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $schoolDays = SchoolHoliday::countSchoolDaysInPeriod($startDate, $endDate);
        
        // Feriados do ano letivo
        $holidays = SchoolHoliday::active()
            ->forYear($schoolYear->id)
            ->orderBy('start_date')
            ->get()
            ->map(function ($holiday) {
                return [
                    'nome' => $holiday->name,
                    'tipo' => $holiday->type->label(),
                    'periodo' => $holiday->getFormattedPeriod(),
                    'duracao' => $holiday->getDurationInDays() . ' dia(s)',
                    'ja_passou' => $holiday->isPast(),
                ];
            });
        
        // Dias letivos por mês
        $monthlySchoolDays = [];
        $currentMonth = $startDate->copy()->startOfMonth();
        $endMonth = $endDate->copy()->endOfMonth();
        
        while ($currentMonth->lte($endMonth)) {
            $monthStart = max($currentMonth->copy()->startOfMonth(), $startDate);
            $monthEnd = min($currentMonth->copy()->endOfMonth(), $endDate);
            
            $schoolDaysInMonth = SchoolHoliday::countSchoolDaysInPeriod($monthStart, $monthEnd);
            
            $monthlySchoolDays[] = [
                'mes' => $currentMonth->locale('pt_BR')->monthName,
                'ano' => $currentMonth->year,
                'dias_letivos' => $schoolDaysInMonth,
            ];
            
            $currentMonth->addMonth();
        }
        
        return response()->json([
            'ano_letivo' => $schoolYear->year,
            'periodo' => $startDate->format('d/m/Y') . ' a ' . $endDate->format('d/m/Y'),
            'total_dias_periodo' => $totalDays,
            'total_dias_letivos' => $schoolDays,
            'total_dias_nao_letivos' => $totalDays - $schoolDays,
            'dias_letivos_por_mes' => $monthlySchoolDays,
            'feriados_e_recessos' => [
                'total' => $holidays->count(),
                'lista' => $holidays,
            ],
        ]);
    }

    /**
     * Exemplo 5: Dias letivos restantes
     */
    public function diasLetivosRestantes(): JsonResponse
    {
        $schoolYear = \App\Models\SchoolYear::current();
        
        if (!$schoolYear) {
            return response()->json([
                'error' => 'Nenhum ano letivo ativo encontrado',
            ], 404);
        }
        
        $today = now();
        $endDate = $schoolYear->ends_at;
        
        // Dias letivos já decorridos
        $schoolDaysPast = SchoolHoliday::countSchoolDaysInPeriod(
            $schoolYear->starts_at,
            $today
        );
        
        // Dias letivos restantes
        $schoolDaysRemaining = SchoolHoliday::countSchoolDaysInPeriod(
            $today,
            $endDate
        );
        
        $totalSchoolDays = $schoolDaysPast + $schoolDaysRemaining;
        $percentCompleted = $totalSchoolDays > 0 
            ? round(($schoolDaysPast / $totalSchoolDays) * 100, 2) 
            : 0;
        
        // Próximos feriados/recessos
        $upcomingBreaks = SchoolHoliday::active()
            ->where('start_date', '>=', $today)
            ->where('school_year_id', $schoolYear->id)
            ->orderBy('start_date')
            ->limit(3)
            ->get()
            ->map(fn($h) => [
                'nome' => $h->name,
                'periodo' => $h->getFormattedPeriod(),
                'dias_ate' => now()->diffInDays($h->start_date),
            ]);
        
        return response()->json([
            'ano_letivo' => $schoolYear->year,
            'dias_letivos_total' => $totalSchoolDays,
            'dias_letivos_decorridos' => $schoolDaysPast,
            'dias_letivos_restantes' => $schoolDaysRemaining,
            'percentual_concluido' => $percentCompleted . '%',
            'proximos_feriados_recessos' => $upcomingBreaks,
            'data_termino' => $endDate->format('d/m/Y'),
            'dias_corridos_ate_termino' => now()->diffInDays($endDate),
        ]);
    }
}
