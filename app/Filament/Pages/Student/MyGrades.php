<?php

namespace App\Filament\Pages\Student;

use App\Models\Grade;
use App\Services\GradeCalculationService;
use App\Support\PermissionAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;

class MyGrades extends Page {

    protected static string|null|\BackedEnum $navigationIcon = 'fas-chart-bar';
    protected static ?string $title                          = 'Minhas Notas';
    protected static ?string $navigationLabel                = 'Minhas Notas';
    protected static ?int    $navigationSort                 = 1;

    public string $selectedPeriod = 'all';

    /**
     * Determina se a página deve ser registrada na navegação.
     *
     * @return bool
     */
    public static function shouldRegisterNavigation(): bool {
        return PermissionAccess::can('student.grades.view');
    }

    /**
     * Determina se o usuário atual pode acessar a página.
     *
     * @return bool
     */
    public static function canAccess(): bool {
        return PermissionAccess::can('student.grades.view');
    }

    /**
     * Retorna o nome da visualização usada pela página.
     *
     * @return string
     */
    public function getView(): string {
        return 'filament.pages.student.my-grades';
    }

    /**
     * Define o período usado para filtrar os dados.
     *
     * @param string $period
     *
     * @return void
     */
    public function setPeriod(string $period): void {
        $this->selectedPeriod = $period;
    }

    /**
     * Retorna os widgets exibidos no cabeçalho da página.
     *
     * @return array
     */
    protected function getHeaderWidgets(): array {
        return [];
    }

    /**
     * Retorna os widgets exibidos no rodapé da página.
     *
     * @return array
     */
    protected function getFooterWidgets(): array {
        return [];
    }

    /**
     * Retorna as ações exibidas no cabeçalho.
     *
     * @return array
     */
    protected function getHeaderActions(): array {
        return [
            Action::make('downloadReportCard')
                ->label('Baixar Boletim (PDF)')
                ->icon('fas-download')
                ->color('success')
                ->visible(fn () => PermissionAccess::can('student.report-card.download'))
                ->action(fn () => $this->downloadReportCard()),
        ];
    }

    /**
     * Retorna todos os dados necessários para o modelo Blade da página de notas.
     *
     * @return array
     */
    public function getPageData(): array {
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
            ->whereHas('schoolYear', fn ($q) => $q->where('is_active', true))
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

        /* Ordena por reprovado, recuperação, cursando e aprovado. */
        $statusOrder = ['failed' => 0, 'recovery' => 1, 'ongoing' => 2, 'approved' => 3];
        usort($subjects, fn ($a, $b) => ($statusOrder[$a['status']] ?? 4) <=> ($statusOrder[$b['status']] ?? 4));

        $col      = collect($subjects);
        $averages = $col->pluck('overall_average')->filter(fn ($v) => $v !== null);

        return [
            'student'      => $student,
            'currentClass' => $currentClass,
            'subjects'     => $subjects,
            'stats'        => [
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

    /* Métodos privados. */

    /**
     * Retorna o rótulo do período selecionado para as notas.
     *
     * @return string
     */
    private function periodLabel(): string {
        $year = now()->year;

        return match ($this->selectedPeriod) {
            'b1'    => "1º Bimestre $year",
            'b2'    => "2º Bimestre $year",
            'b3'    => "3º Bimestre $year",
            'b4'    => "4º Bimestre $year",
            default => "Ano Letivo $year",
        };
    }

    /**
     * Gera o download do boletim escolar do aluno.
     *
     * @return mixed
     */
    private function downloadReportCard() {
        $data = $this->getPageData();

        if (!$data['student'] || !$data['currentClass']) {
            return;
        }

        $pdf = Pdf::loadView('pdf.report-card', [
            'student'        => $data['student'],
            'currentClass'   => $data['currentClass'],
            'subjects'       => $data['subjects'],
            'stats'          => $data['stats'],
            'selectedPeriod' => $data['selected_period'],
            'generatedAt'    => now(),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->stream()),
            'boletim-' . $data['student']->registration_number . '.pdf'
        );
    }
}
