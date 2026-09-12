<?php

namespace App\Filament\Pages\Student;

use App\Enums\TermType;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Services\GradeCalculationService;
use App\Support\PermissionAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

class MyGrades extends Page implements HasTable {

    use InteractsWithTable;

    protected static string|null|\BackedEnum $navigationIcon = 'fas-chart-bar';
    protected static ?string $title                          = 'Minhas Notas';
    protected static ?string $navigationLabel                = 'Minhas Notas';
    protected static ?int    $navigationSort                 = 1;

    public const PERIODS = ['b1' => '1º bimestre', 'b2' => '2º bimestre', 'b3' => '3º bimestre', 'b4' => '4º bimestre', 'all' => 'Visão anual'];

    #[Locked]
    public string $selectedPeriod = 'b1';

    protected ?array $cachedPageData = null;

    public function mount(): void {
        $currentClass = $this->currentClass();
        if (!$currentClass) {
            return;
        }

        $term = $currentClass->schoolYear->terms()
            ->where('type', TermType::BIMESTER)
            ->whereBetween('sequence', [1, 4])
            ->whereDate('starts_at', '<=', today())
            ->whereDate('ends_at', '>=', today())
            ->first();

        $latestTerm = Grade::where('student_id', auth()->user()?->student?->id)
            ->where('class_id', $currentClass->id)
            ->whereNotNull('score')
            ->whereIn('term', ['b1', 'b2', 'b3', 'b4'])
            ->orderByDesc('term')
            ->value('term');

        $this->selectedPeriod = $term ? 'b'.$term->sequence : ($latestTerm?->value ?? 'b1');
    }

    protected function currentClass(): ?SchoolClass {
        return auth()->user()?->student?->classes()
            ->whereHas('schoolYear', fn ($q) => $q->where('is_active', true))
            ->with(['schoolYear', 'gradeLevel'])
            ->first();
    }

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
        abort_unless(array_key_exists($period, self::PERIODS), 422);
        $this->selectedPeriod = $period;
        $this->cachedPageData = null;
        $this->resetTable();
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
                ->label('Baixar boletim')
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
        return $this->cachedPageData ??= $this->buildPageData();
    }

    protected function buildPageData(): array {
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

        $currentClass = $this->currentClass();

        if (!$currentClass) {
            return array_merge($empty, ['student' => $student]);
        }

        $query = Grade::where('student_id', $student->id)
            ->where('class_id', $currentClass->id)
            ->with(['subject', 'assessment'])
            ->orderBy('term')
            ->orderBy('sequence');

        $allGrades = $query->get();
        $service   = app(GradeCalculationService::class);
        $subjects  = [];

        foreach ($allGrades->groupBy('subject_id') as $grades) {
            /** @var \Illuminate\Support\Collection $grades */
            $subject    = $grades->first()->subject;
            $periodGrades = $this->selectedPeriod === 'all'
                ? $grades
                : $grades->filter(fn (Grade $grade) => $grade->term?->value === $this->selectedPeriod);
            $report     = $service->subjectReport(collect($periodGrades));
            $subjects[] = array_merge(['subject' => $subject], $report);
        }

        $reportedIds = collect($subjects)->pluck('subject.id');
        foreach ($currentClass->subjects()->whereNotIn('subjects.id', $reportedIds)->get() as $subject) {
            $subjects[] = ['subject' => $subject, ...$service->subjectReport(collect())];
        }

        /* Prioriza as disciplinas que precisam de atenção e desempata pelo nome. */
        $statusOrder = ['failed' => 0, 'recovery' => 1, 'ongoing' => 2, 'approved' => 3];
        usort($subjects, fn ($a, $b) => (($statusOrder[$a['status']] ?? 4) <=> ($statusOrder[$b['status']] ?? 4))
            ?: strcasecmp($a['subject']?->name ?? '', $b['subject']?->name ?? ''));

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
            'period_label'    => $this->periodLabel($currentClass->schoolYear?->year),
            'min_approval'    => GradeCalculationService::MIN_APPROVAL,
        ];
    }

    public static function progressLabel(?float $average): string {
        return $average === null ? 'Sem nota' : ($average >= GradeCalculationService::MIN_APPROVAL ? 'Na média' : 'Abaixo da média');
    }

    public static function progressColor(?float $average): string {
        return $average === null ? 'gray' : ($average >= GradeCalculationService::MIN_APPROVAL ? 'success' : 'warning');
    }

    public function table(Table $table): Table {
        $format = fn ($state): string => number_format((float) $state, 1, ',', '');

        return $table
            ->heading(fn () => $this->selectedPeriod === 'all' ? 'Médias por bimestre' : 'Notas por disciplina')
            ->description(fn () => $this->getPageData()['period_label'].' · Referência: média 6,0')
            ->records(function (?string $search): Collection {
                return collect($this->getPageData()['subjects'])
                    ->mapWithKeys(fn (array $item) => [$item['subject']->id => [
                        ...$item,
                        'subject_name' => $item['subject']->name,
                    ]])
                    ->when(filled($search), fn (Collection $rows) => $rows->filter(
                        fn (array $row) => str_contains(Str::lower($row['subject_name']), Str::lower($search)),
                    ));
            })
            ->columns([
                ViewColumn::make('subject_name')
                    ->label('Disciplina')
                    ->view('filament.tables.columns.student-grade-subject'),
                ...collect(['b1', 'b2', 'b3', 'b4'])->map(fn (string $term) => TextColumn::make('terms.'.$term.'.final_average')
                    ->label(self::PERIODS[$term])
                    ->visible(fn () => $this->selectedPeriod === 'all')
                    ->visibleFrom('md')
                    ->alignCenter()
                    ->placeholder('—')
                    ->formatStateUsing($format))->all(),
                TextColumn::make('overall_average')
                    ->label(fn () => $this->selectedPeriod === 'all' ? 'Média parcial' : 'Média')
                    ->alignCenter()
                    ->weight(FontWeight::Bold)
                    ->color(fn (array $record) => self::progressColor($record['overall_average']))
                    ->placeholder('—')
                    ->formatStateUsing($format),
                TextColumn::make('progress')
                    ->label('Acompanhamento')
                    ->visibleFrom('md')
                    ->state(fn (array $record) => self::progressLabel($record['overall_average']))
                    ->badge()
                    ->color(fn (array $record) => self::progressColor($record['overall_average'])),
            ])
            ->recordTitleAttribute('subject_name')
            ->recordActions([
                Action::make('gradeDetails')
                    ->label('Detalhes')
                    ->icon('heroicon-o-chevron-right')
                    ->color('gray')
                    ->modalHeading(fn (array $record) => $record['subject_name'])
                    ->modalDescription(fn () => $this->getPageData()['period_label'])
                    ->modalWidth(Width::ThreeExtraLarge)
                    ->modalContent(fn (array $record) => view('filament.pages.student.partials.grade-details', [
                        'item' => $record, 'selectedPeriod' => $this->selectedPeriod,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar'),
            ])
            ->searchable()
            ->searchPlaceholder('Buscar disciplina')
            ->paginated(false)
            ->striped()
            ->emptyStateHeading('Nenhuma disciplina encontrada')
            ->emptyStateDescription('Não há notas registradas ou a busca não encontrou uma disciplina.');
    }

    /* Métodos privados. */

    /**
     * Retorna o rótulo do período selecionado para as notas.
     *
     * @return string
     */
    private function periodLabel(?int $year = null): string {
        $year ??= now()->year;

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
        abort_unless(PermissionAccess::can('student.report-card.download'), 403);
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
