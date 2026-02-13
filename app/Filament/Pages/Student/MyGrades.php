<?php

namespace App\Filament\Pages\Student;

use App\Filament\Widgets\StudentGradesWidget;
use App\Filament\Widgets\StudentGradesStatsWidget;
use App\Models\Grade;
use App\Models\Student;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;

class MyGrades extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $title = 'Minhas Notas';
    protected static ?string $navigationLabel = 'Minhas Notas';
    protected static ?int $navigationSort = 1;

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

    protected function getHeaderWidgets(): array
    {
        return [
            StudentGradesStatsWidget::class,
            StudentGradesWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadReportCard')
                ->label('Baixar Boletim')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return $this->downloadReportCard();
                }),
        ];
    }

    protected function downloadReportCard()
    {
        $user = auth()->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return redirect()->back()->with('error', 'Estudante não encontrado');
        }

        $grades = Grade::query()
            ->where('student_id', $student->id)
            ->with(['subject', 'schoolClass'])
            ->orderBy('term')
            ->orderBy('subject_id')
            ->orderBy('sequence')
            ->get();

        // Agrupa notas por disciplina e bimestre
        $reportData = $grades->groupBy('subject.name')->map(function ($subjectGrades) {
            return $subjectGrades->groupBy('term')->map(function ($termGrades) {
                $average = $termGrades->avg('score');
                return [
                    'grades' => $termGrades,
                    'average' => $average,
                ];
            });
        });

        // Calcula média geral
        $overallAverage = $grades->avg('score');

        $pdf = Pdf::loadView('pdf.report-card', [
            'student' => $student,
            'reportData' => $reportData,
            'overallAverage' => $overallAverage,
            'generatedAt' => now(),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'boletim-' . $student->registration_number . '.pdf');
    }
}
