<?php

namespace App\Filament\Widgets;

use App\Enums\Term;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class StudentGradesWidget extends TableWidget
{

    protected static ?string $heading = 'Minhas Notas';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('student');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $user = auth()->user();

                if (!$user || !$user->student) {
                    return Grade::query()->whereRaw('1 = 0');
                }

                return Grade::query()
                    ->where('student_id', $user->student->id)
                    ->with(['subject', 'schoolClass'])
                    ->orderBy('term')          // organiza por bimestre
                    ->orderBy('subject_id')    // depois por disciplina
                    ->orderBy('sequence');     // ordem da prova no bimestre
            })

            // AGRUPAMENTOS: 1º por bimestre, 2º por disciplina
            ->groups([
                Group::make('term')
                    ->label('Bimestre')
                    ->getTitleFromRecordUsing(function (Grade $record): string {
                        $term = $record->term instanceof Term
                            ? $record->term->value
                            : $record->term;

                        return match ($term) {
                            'b1' => '1º Bimestre',
                            'b2' => '2º Bimestre',
                            'b3' => '3º Bimestre',
                            'b4' => '4º Bimestre',
                            default => (string)$term,
                        };
                    }),

                Group::make('subject.name')
                    ->label('Disciplina'),
            ])
            ->columns([
                // dentro da disciplina, cada linha é uma avaliação/prova
                TextColumn::make('subject.name')
                    ->label('Disciplina')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('schoolClass.name')
                    ->label('Turma')
                    ->toggleable(),

                TextColumn::make('sequence')
                    ->label('Prova')
                    ->formatStateUsing(fn($state) => 'Prova ' . $state)
                    ->sortable(),

                TextColumn::make('assessment_type')
                    ->label('Tipo')
                    ->formatStateUsing(function ($state) {
                        // se for enum AssessmentType, pega value/label; se for string, usa direto
                        $value = is_object($state) && method_exists($state, 'value')
                            ? $state->value
                            : $state;

                        return match ($value) {
                            'test' => 'Prova',
                            'quiz' => 'Quiz',
                            'work' => 'Trabalho',
                            'project' => 'Projeto',
                            'participation' => 'Participação',
                            'recovery' => 'Recuperação',
                            default => $value,
                        };
                    })
                    ->badge(),

                TextColumn::make('score')
                    ->label('Nota')
                    ->numeric(decimalPlaces: 2)
                    ->weight('bold')
                    ->sortable()
                    ->summarize([
                        Average::make()
                            ->label('Média da disciplina no bimestre')
                            ->formatStateUsing(function ($state) {
                                if ($state === null) {
                                    return '-';
                                }

                                return number_format((float) $state, 2, ',', '.');
                            }),
                    ]),

                TextColumn::make('max_score')
                    ->label('Máx.')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('weight')
                    ->label('Peso')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('date_recorded')
                    ->label('Data')
                    ->date('d/m/Y'),
            ])
            ->filters([
                SelectFilter::make('subject_id')
                    ->label('Disciplina')
                    ->options(fn () => Subject::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->indicator('Disciplina'),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}
