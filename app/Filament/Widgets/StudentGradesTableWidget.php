<?php

namespace App\Filament\Widgets;

use App\Enums\Term;
use App\Models\Grade;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class StudentGradesTableWidget extends Widget
{
    protected string $view = 'filament.widgets.student-grades-table';

    protected static ?string $heading = 'Minhas Notas';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('student');
    }

    public function getViewData(): array
    {
        $user = auth()->user();

        if (!$user || !$user->student) {
            return [
                'gradesByTerm' => collect(),
                'assessmentColumns' => [],
                'termLabels' => [],
                'termAverages' => [],
            ];
        }

        // Get current active class
        $currentClass = $user->student->classes()
            ->whereHas('schoolYear', fn ($q) => $q->where('is_active', true))
            ->first();

        if (!$currentClass) {
            return [
                'gradesByTerm' => collect(),
                'assessmentColumns' => [],
                'termLabels' => [],
                'termAverages' => [],
            ];
        }

        // Busca notas do aluno da turma atual (ano letivo ativo)
        $grades = Grade::query()
            ->where('student_id', $user->student->id)
            ->where('class_id', $currentClass->id)
            ->with(['subject', 'schoolClass'])
            ->orderBy('term')
            ->orderBy('subject_id')
            ->orderBy('sequence')
            ->get();

        // Termos (bimestres)
        $termLabels = [
            'b1' => '1º Bimestre',
            'b2' => '2º Bimestre',
            'b3' => '3º Bimestre',
            'b4' => '4º Bimestre',
        ];

        // Agrupa por bimestre
        $gradesByTerm = new Collection();
        $assessmentColumns = new Collection();
        $termAverages = [];

        foreach ($grades->groupBy('term') as $term => $termGrades) {
            $disciplines = new Collection();

            foreach ($termGrades->groupBy('subject_id') as $subjectId => $subjectGrades) {
                $subjectName = $subjectGrades->first()->subject->name ?? 'Desconhecida';
                $className = $subjectGrades->first()->schoolClass->name ?? 'Desconhecida';

                // Organiza notas por sequência (prova)
                $gradesBySequence = $subjectGrades->groupBy('sequence')->map(function ($seqGrades) {
                    return $seqGrades->map(fn($g) => $g->score)->average();
                });

                // Coleta todas as colunas de avaliação (provas únicas)
                $subjectGrades->each(function ($grade) use ($assessmentColumns) {
                    $label = 'PROVA ' . $grade->sequence;
                    if (!$assessmentColumns->contains($label)) {
                        $assessmentColumns->push($label);
                    }
                });

                // Monta os dados da disciplina
                $disciplineData = [
                    'name' => $subjectName,
                    'class' => $className,
                    'grades' => [],
                    'average' => $gradesBySequence->avg(),
                    'lastDate' => $subjectGrades->max('date_recorded')?->format('d/m/Y'),
                ];

                // Preenche as notas por sequência
                foreach ($gradesBySequence as $sequence => $score) {
                    $disciplineData['grades']['PROVA ' . $sequence] = $score;
                }

                $disciplines->put($subjectId, $disciplineData);
            }

            // Calcula média do bimestre
            $termAverages[$term] = $disciplines->map(fn($d) => $d['average'])->average();

            $gradesByTerm->put($term, $disciplines);
        }

        return [
            'gradesByTerm' => $gradesByTerm,
            'assessmentColumns' => $assessmentColumns->unique()->sort()->values(),
            'termLabels' => $termLabels,
            'termAverages' => $termAverages,
        ];
    }
}
