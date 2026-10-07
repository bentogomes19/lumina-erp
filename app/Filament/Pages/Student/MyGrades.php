<?php

namespace App\Filament\Pages\Student;

use App\Enums\AssessmentType;
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
    public string $selectedPeriod = 'all';

    protected ?array $cachedPageData = null;

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
                ->color('primary')
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

    protected function buildPageData(?string $period = null): array {
        $period ??= $this->selectedPeriod;
        $student = auth()->user()?->student;

        $empty = [
            'student'         => null,
            'currentClass'    => null,
            'subjects'        => [],
            'assessment_columns' => [],
            'stats'           => ['total' => 0, 'approved' => 0, 'recovery' => 0, 'failed' => 0, 'ongoing' => 0, 'average' => null],
            'selected_period' => $period,
            'period_label'    => $this->periodLabel(null, $period),
            'min_approval'    => app(GradeCalculationService::class)->minimumApproval(),
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
            $periodGrades = $period === 'all'
                ? $grades
                : $grades->filter(fn (Grade $grade) => $grade->term?->value === $period);
            $report     = $service->subjectReport(collect($periodGrades));
            $subjects[] = array_merge(['subject' => $subject], $report);
        }

        $reportedIds = collect($subjects)->pluck('subject.id');
        foreach ($currentClass->subjects()->whereNotIn('subjects.id', $reportedIds)->get() as $subject) {
            $subjects[] = ['subject' => $subject, ...$service->subjectReport(collect())];
        }

        usort($subjects, fn (array $a, array $b) =>
            strnatcasecmp(Str::ascii($a['subject']?->name ?? ''), Str::ascii($b['subject']?->name ?? ''))
            ?: strnatcasecmp($a['subject']?->name ?? '', $b['subject']?->name ?? '')
            ?: (($a['subject']?->id ?? 0) <=> ($b['subject']?->id ?? 0)));

        $col      = collect($subjects);
        $averages = $col->pluck('overall_average')->filter(fn ($v) => $v !== null);

        $assessmentColumns = [];
        if ($period !== 'all') {
            foreach (AssessmentType::cases() as $type) {
                if ($type === AssessmentType::RECOVERY) {
                    continue;
                }

                $maximum = $col->max(function (array $item) use ($period, $type): int {
                    $prefix = $type->value.'_';
                    $slots = array_keys($this->assessmentScoresForPeriod($item, $period));

                    return collect($slots)
                        ->filter(fn (string $key) => str_starts_with($key, $prefix))
                        ->map(fn (string $key) => (int) substr($key, strlen($prefix)))
                        ->max() ?? 0;
                }) ?? 0;

                // O boletim bimestral mantém as duas provas previstas mesmo antes dos lançamentos.
                if ($type === AssessmentType::TEST) {
                    $maximum = max(2, $maximum);
                }

                for ($number = 1; $number <= $maximum; $number++) {
                    $assessmentColumns[] = [
                        'key' => $type->value.'_'.$number,
                        'label' => $type->label().' '.$number,
                    ];
                }
            }
        }

        return [
            'student'      => $student,
            'currentClass' => $currentClass,
            'subjects'     => $subjects,
            'assessment_columns' => $assessmentColumns,
            'stats'        => [
                'total'    => $col->count(),
                'approved' => $col->where('status', 'approved')->count(),
                'recovery' => $col->where('status', 'recovery')->count(),
                'failed'   => $col->where('status', 'failed')->count(),
                'ongoing'  => $col->where('status', 'ongoing')->count(),
                'average'  => $averages->isNotEmpty() ? round($averages->avg(), 1) : null,
            ],
            'selected_period' => $period,
            'period_label'    => $this->periodLabel($currentClass->schoolYear?->year, $period),
            'min_approval'    => $service->minimumApproval(),
        ];
    }

    public static function progressLabel(?float $average): string {
        return $average === null ? 'Sem nota' : ($average >= app(GradeCalculationService::class)->minimumApproval() ? 'Na média' : 'Abaixo da média');
    }

    public static function progressColor(?float $average): string {
        return $average === null ? 'gray' : ($average >= app(GradeCalculationService::class)->minimumApproval() ? 'success' : 'warning');
    }

    public function table(Table $table): Table {
        $format = fn ($state): string => number_format((float) $state, 1, ',', '');

        return $table
            ->heading(fn () => $this->selectedPeriod === 'all' ? 'Meu boletim' : 'Boletim do '.self::PERIODS[$this->selectedPeriod])
            ->description(fn () => $this->getPageData()['period_label'].' · Média da escola: '.number_format($this->getPageData()['min_approval'], 1, ',', ''))
            ->records(function (): Collection {
                return collect($this->getPageData()['subjects'])
                    ->mapWithKeys(fn (array $item) => [$item['subject']->id => [
                        ...$item,
                        'subject_name' => $item['subject']->name,
                        'assessment_scores' => $this->assessmentScores($item),
                    ]]);
            })
            ->columns([
                TextColumn::make('subject_name')
                    ->label('Disciplina')
                    ->weight(FontWeight::SemiBold)
                    ->wrap(),
                ...collect($this->getPageData()['assessment_columns'])->map(fn (array $column) => TextColumn::make('assessment_scores.'.$column['key'])
                    ->label($column['label'])
                    ->alignCenter()
                    ->placeholder('—')
                    ->formatStateUsing($format))->all(),
                ...collect(['b1', 'b2', 'b3', 'b4'])->map(fn (string $term) => TextColumn::make('terms.'.$term.'.final_average')
                    ->label(self::PERIODS[$term])
                    ->visible(fn () => $this->selectedPeriod === 'all')
                    ->alignCenter()
                    ->placeholder('—')
                    ->color(fn ($state) => self::progressColor($state === null ? null : (float) $state))
                    ->formatStateUsing($format))->all(),
                TextColumn::make('term_recovery')
                    ->label('Recuperação')
                    ->visible(fn () => $this->selectedPeriod !== 'all')
                    ->state(fn (array $record) => data_get($record, 'terms.'.$this->selectedPeriod.'.recovery.score'))
                    ->alignCenter()
                    ->placeholder('—')
                    ->formatStateUsing($format),
                TextColumn::make('overall_average')
                    ->label(fn () => $this->selectedPeriod === 'all' ? 'Média final*' : 'Média final')
                    ->alignCenter()
                    ->weight(FontWeight::Bold)
                    ->color(fn (array $record) => self::progressColor($record['overall_average']))
                    ->placeholder('—')
                    ->formatStateUsing($format),
                TextColumn::make('recovery_summary')
                    ->label('Recuperação')
                    ->visible(fn () => $this->selectedPeriod === 'all')
                    ->state(fn (array $record) => self::recoverySummary($record))
                    ->alignCenter()
                    ->placeholder('—'),
                TextColumn::make('progress')
                    ->label('Situação')
                    ->visible(fn () => $this->selectedPeriod === 'all')
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
                        'minimumGrade' => $this->getPageData()['min_approval'],
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar'),
            ])
            ->paginated(false)
            ->striped()
            ->emptyStateHeading('Nenhuma disciplina encontrada')
            ->emptyStateDescription('Não há disciplinas registradas para esta turma.');
    }

    private function assessmentScores(array $item): array {
        if ($this->selectedPeriod === 'all') {
            return [];
        }

        return $this->assessmentScoresForPeriod($item, $this->selectedPeriod);
    }

    private function assessmentScoresForPeriod(array $item, string $period): array {
        $scores = [];
        foreach (AssessmentType::cases() as $type) {
            if ($type === AssessmentType::RECOVERY) {
                continue;
            }

            $grades = $item['terms'][$period]['grades']
                ->filter(fn (Grade $grade) => $grade->assessment_type === $type)
                ->values();

            foreach ($grades as $grade) {
                $number = max(1, (int) $grade->sequence);
                while (array_key_exists($type->value.'_'.$number, $scores)) {
                    $number++;
                }
                $scores[$type->value.'_'.$number] = $grade->score;
            }
        }

        return $scores;
    }

    public static function recoverySummary(array $record): string {
        $scores = collect(['b1', 'b2', 'b3', 'b4'])
            ->map(fn (string $term) => [$term, data_get($record, "terms.$term.recovery.score")])
            ->filter(fn (array $entry) => $entry[1] !== null)
            ->map(fn (array $entry) => substr($entry[0], 1).'º: '.number_format((float) $entry[1], 1, ',', ''));

        return $scores->isEmpty() ? '—' : $scores->implode(' · ');
    }

    /* Métodos privados. */

    /**
     * Retorna o rótulo do período selecionado para as notas.
     *
     * @return string
     */
    private function periodLabel(?int $year = null, ?string $period = null): string {
        $year ??= now()->year;
        $period ??= $this->selectedPeriod;

        return match ($period) {
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
        $data = $this->buildPageData('all');

        if (!$data['student'] || !$data['currentClass']) {
            return;
        }

        $pdf = Pdf::loadView('pdf.report-card', [
            'student'        => $data['student'],
            'currentClass'   => $data['currentClass'],
            'subjects'       => $data['subjects'],
            'stats'          => $data['stats'],
            'selectedPeriod' => $data['selected_period'],
            'minimumGrade'   => $data['min_approval'],
            'generatedAt'    => now(),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->stream()),
            'boletim-' . $data['student']->registration_number . '.pdf'
        );
    }
}
