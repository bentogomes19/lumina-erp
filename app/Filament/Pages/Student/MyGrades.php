<?php

namespace App\Filament\Pages\Student;

use App\Models\Grade;
use App\Services\GradeCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;

class MyGrades extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $title                          = 'Minhas Notas';
    protected static ?string $navigationLabel                = 'Minhas Notas';
    protected static ?int    $navigationSort                 = 1;

    public string $selectedPeriod = 'all';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('student');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('student') ?? false;
    }

    public function getView(): string
    {
        return 'filament.pages.student.my-grades';
    }

    public function setPeriod(string $period): void
    {
        $this->selectedPeriod = $period;
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadReportCard')
                ->label('Baixar Boletim (PDF)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn() => $this->downloadReportCard()),
        ];
    }

    /**
     * Returns all data needed by the blade view.
     */
    public function getPageData(): array
    {
        $student = auth()->user()?->student;

        $empty = [
            'student'         => null,
            'currentClass'    => null,
            'subjects'        => [],
            'stats'           => ['total' => 0, 'approved' => 0, 'recovery' => 0, 'failed' => 0, 'ongoing' => 0, 'average' => null],
            'selected_period' => $this->selectedPeriod,
            'period_label'    => $this->periodLabel(),
            'min_approval'    => GradeCalculationService::MIN_APPROVAL,
        ];

        if (!$student) {
            return $empty;
        }

        $currentClass = $student->classes()
            ->whereHas('schoolYear', fn($q) => $q->where('is_active', true))
            ->with(['schoolYear', 'gradeLevel'])
            ->first();

        if (!$currentClass) {
            return array_merge($empty, ['student' => $student]);
        }

        $query = Grade::where('student_id', $student->id)
            ->where('class_id', $currentClass->id)
            ->with(['subject'])
            ->orderBy('term')
            ->orderBy('sequence');

        if ($this->selectedPeriod !== 'all') {
            $query->where('term', $this->selectedPeriod);
        }

        $allGrades = $query->get();
        $service   = app(GradeCalculationService::class);
        $subjects  = [];

        foreach ($allGrades->groupBy('subject_id') as $grades) {
            /** @var \Illuminate\Support\Collection $grades */
            $subject    = $grades->first()->subject;
            $report     = $service->subjectReport(collect($grades));
            $subjects[] = array_merge(['subject' => $subject], $report);
        }

        // Sort: failed → recovery → ongoing → approved
        $statusOrder = ['failed' => 0, 'recovery' => 1, 'ongoing' => 2, 'approved' => 3];
        usort($subjects, fn($a, $b) => ($statusOrder[$a['status']] ?? 4) <=> ($statusOrder[$b['status']] ?? 4));

        $col      = collect($subjects);
        $averages = $col->pluck('overall_average')->filter(fn($v) => $v !== null);

        return [
            'student'         => $student,
            'currentClass'    => $currentClass,
            'subjects'        => $subjects,
            'stats'           => [
                'total'    => $col->count(),
                'approved' => $col->where('status', 'approved')->count(),
                'recovery' => $col->where('status', 'recovery')->count(),
                'failed'   => $col->where('status', 'failed')->count(),
                'ongoing'  => $col->where('status', 'ongoing')->count(),
                'average'  => $averages->isNotEmpty() ? round($averages->avg(), 1) : null,
            ],
            'selected_period' => $this->selectedPeriod,
            'period_label'    => $this->periodLabel(),
            'min_approval'    => GradeCalculationService::MIN_APPROVAL,
        ];
    }

    // ── Private ──────────────────────────────────────────────────────────────

    private function periodLabel(): string
    {
        $year = now()->year;

        return match ($this->selectedPeriod) {
            'b1'    => "1º Bimestre $year",
            'b2'    => "2º Bimestre $year",
            'b3'    => "3º Bimestre $year",
            'b4'    => "4º Bimestre $year",
            default => "Ano Letivo $year",
        };
    }

    private function downloadReportCard()
    {
        $data = $this->getPageData();

        if (!$data['student'] || !$data['currentClass']) {
            return;
        }

        $pdf = Pdf::loadView('pdf.report-card', [
            'student'      => $data['student'],
            'currentClass' => $data['currentClass'],
            'subjects'     => $data['subjects'],
            'stats'        => $data['stats'],
            'generatedAt'  => now(),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn() => print($pdf->stream()),
            'boletim-' . $data['student']->registration_number . '.pdf'
        );
    }
}
