<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentAttendanceStatsWidget extends BaseWidget
{
    public static function canView(): bool
    {
        return \App\Support\PermissionAccess::can('student.attendance.view');
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return [];
        }

        $attendances = Attendance::where('student_id', $student->id)->get();

        $total = $attendances->count();
        $present = $attendances->where('status', 'present')->count();
        $absent = $attendances->where('status', 'absent')->count();
        $late = $attendances->where('status', 'late')->count();

        $attendanceRate = $total > 0 ? round(($present / $total) * 100, 1) : 0;
        $absenceRate = $total > 0 ? round(($absent / $total) * 100, 1) : 0;

        return [
            Stat::make('Taxa de Presença', $attendanceRate . '%')
                ->description('Presença nas aulas')
                ->descriptionIcon('fas-circle-check')
                ->color($attendanceRate >= 75 ? 'success' : ($attendanceRate >= 60 ? 'warning' : 'danger'))
                ->chart($this->getAttendanceTrend()),

            Stat::make('Total de Presenças', $present)
                ->description('Dias presentes')
                ->descriptionIcon('fas-calendar-days')
                ->color('success'),

            Stat::make('Faltas', $absent)
                ->description('Total de ausências')
                ->descriptionIcon('fas-circle-xmark')
                ->color($absent > 10 ? 'danger' : ($absent > 5 ? 'warning' : 'success')),

            Stat::make('Atrasos', $late)
                ->description('Chegada após início')
                ->descriptionIcon('fas-clock')
                ->color($late > 5 ? 'warning' : 'gray'),
        ];
    }

    protected function getAttendanceTrend(): array
    {
        $user = auth()->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return [];
        }

        // Calcula taxa de presença por semana nas últimas 7 semanas
        $weeklyRates = [];
        for ($i = 6; $i >= 0; $i--) {
            $startDate = now()->subWeeks($i)->startOfWeek();
            $endDate = now()->subWeeks($i)->endOfWeek();

            $weekTotal = Attendance::where('student_id', $student->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->count();

            $weekPresent = Attendance::where('student_id', $student->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('status', 'present')
                ->count();

            $weeklyRates[] = $weekTotal > 0 ? round(($weekPresent / $weekTotal) * 100) : 0;
        }

        return $weeklyRates;
    }
}
