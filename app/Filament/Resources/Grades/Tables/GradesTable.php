<?php

namespace App\Filament\Resources\Grades\Tables;

use App\Enums\AssessmentType;
use App\Enums\Term;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Subject;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GradesTable
{
    public static function configure(Table $table): Table
    {
        $user     = auth()->user();
        $teacher  = $user?->teacher;

        // ---------- Defaults para filtros (quando for professor) ----------
        $defaultClassId   = null;
        $defaultSubjectId = null;

        if ($teacher) {
            $firstAssignment = $teacher->teacherAssignments()
                ->orderBy('class_id')
                ->orderBy('subject_id')
                ->first();

            if ($firstAssignment) {
                $defaultClassId   = $firstAssignment->class_id;
                $defaultSubjectId = $firstAssignment->subject_id;
            }
        }

        return $table
            // ---------- Escopo da query ----------
            ->modifyQueryUsing(function (Builder $query) use ($teacher) {
                // Admin / outros cargos: não restringe
                if (! $teacher) {
                    return $query;
                }

                // IDs de turmas e disciplinas que o professor ministra
                $classIds = $teacher->teacherAssignments()
                    ->pluck('class_id')
                    ->unique()
                    ->values()
                    ->all();

                $subjectIds = $teacher->teacherAssignments()
                    ->pluck('subject_id')
                    ->unique()
                    ->values()
                    ->all();

                if (empty($classIds) || empty($subjectIds)) {
                    // se não tiver vínculo, não mostra nada
                    return $query->whereRaw('1 = 0');
                }

                return $query
                    ->whereIn('class_id', $classIds)
                    ->whereIn('subject_id', $subjectIds);
            })

            // ---------- Colunas ----------
            ->columns([
                TextColumn::make('schoolClass.name')
                    ->label('Turma')
                    ->searchable(),

                TextColumn::make('subject.name')
                    ->label('Disciplina')
                    ->searchable(),

                TextColumn::make('enrollment.student.name')
                    ->label('Aluno')
                    ->searchable(),

                BadgeColumn::make('term')
                    ->label('Período')
                    ->formatStateUsing(function ($state) {
                        if ($state instanceof Term) {
                            return $state->name;
                        }
                        $enum = Term::tryFrom((string) $state);

                        return $enum?->name ?? strtoupper((string) $state);
                    })
                    ->color(function ($state) {
                        $value = $state instanceof Term ? $state->value : (string) $state;

                        return match ($value) {
                            'b1' => 'primary',
                            'b2' => 'success',
                            'b3' => 'warning',
                            'b4' => 'danger',
                            default => 'gray',
                        };
                    }),

                // Tipo de avaliação
                BadgeColumn::make('assessment_type')
                    ->label('Tipo')
                    ->formatStateUsing(function ($state) {
                        if ($state instanceof AssessmentType) {
                            return $state->name;
                        }

                        $enum = AssessmentType::tryFrom((string) $state);

                        return $enum?->name ?? (string) $state;
                    })
                    ->color(function ($state) {
                        $value = $state instanceof AssessmentType ? $state->value : (string) $state;

                        return match ($value) {
                            'test'          => 'primary',
                            'quiz'          => 'info',
                            'work'          => 'warning',
                            'project'       => 'gray',
                            'participation' => 'success',
                            'recovery'      => 'danger',
                            default         => 'gray',
                        };
                    }),

                TextColumn::make('score')
                    ->label('Nota')
                    ->numeric(2)
                    ->alignRight(),

                TextColumn::make('max_score')
                    ->label('Máx.')
                    ->numeric(2)
                    ->alignRight()
                    ->toggleable(),

                TextColumn::make('weight')
                    ->label('Peso')
                    ->numeric(2)
                    ->alignRight()
                    ->toggleable(),

                TextColumn::make('percent')
                    ->label('%')
                    ->state(function (Grade $record) {
                        return $record->percent !== null
                            ? $record->percent . '%'
                            : '—';
                    })
                    ->alignRight(),

                TextColumn::make('date_recorded')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
            ])

            // ---------- Filtros ----------
            ->filters([
                // Período / bimestre (default = atual)
                SelectFilter::make('term')
                    ->label('Período')
                    ->options(Term::options())
                    ->default(null),

                SelectFilter::make('assessment_type')
                    ->label('Tipo')
                    ->options(AssessmentType::options()),

                // Turma
                SelectFilter::make('class_id')
                    ->label('Turma')
                    ->options(function () use ($teacher) {
                        if ($teacher) {
                            // só turmas do professor
                            return $teacher->classes()
                                ->orderBy('classes.name')
                                ->pluck('classes.name', 'classes.id');
                        }

                        // admin: todas as turmas
                        return SchoolClass::query()
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->default($defaultClassId),

                // Disciplina
                SelectFilter::make('subject_id')
                    ->label('Disciplina')
                    ->options(function () use ($teacher) {
                        if ($teacher) {
                            return $teacher->teacherAssignments()
                                ->join('subjects', 'subjects.id', '=', 'teacher_assignments.subject_id')
                                ->orderBy('subjects.name')
                                ->pluck('subjects.name', 'subjects.id');
                        }

                        return Subject::query()
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->default($defaultSubjectId),
            ])

            // ---------- Ações ----------
            ->recordActions([
                EditAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('lock')
                        ->label('Fechar notas (selecionadas)')
                        ->icon('heroicon-o-lock-closed')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['locked_at' => now()])),
                ]),
            ]);
    }
}
