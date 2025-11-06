<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class TeacherAttendanceWidget extends TableWidget
{
    protected static ?string $heading = 'Lançamento de Faltas';
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('teacher');
    }

    public function table(Table $table): Table
    {
        // Professor logado
        $teacher = auth()->user()->teacher ?? null;

        // Turma padrão: primeira turma do professor
        $defaultClassId = null;
        if ($teacher) {
            $defaultClassId = $teacher->classes()
                ->orderBy('classes.name')
                ->value('classes.id');
        }

        return $table
            // Alunos com matrícula (enrollments)
            ->query(function () use ($defaultClassId): Builder {
                return Student::query()
                    ->join('enrollments', 'enrollments.student_id', '=', 'students.id')
                    ->when($defaultClassId, fn ($q) => $q->where('enrollments.class_id', $defaultClassId))
                    ->select('students.*', 'enrollments.class_id as enrollment_class_id')
                    ->orderBy('students.name');
            })

            ->filters([
                // TURMA – já vem com uma turma do professor selecionada
                SelectFilter::make('class_id')
                    ->label('Turma')
                    ->options(function () use ($teacher) {
                        if (! $teacher) {
                            return [];
                        }

                        return $teacher->classes()
                            ->orderBy('classes.name')
                            ->pluck('classes.name', 'classes.id');
                    })
                    ->default($defaultClassId)
                    ->query(function (Builder $query, $state) use ($defaultClassId) {
                        // normaliza possível array
                        if (is_array($state)) {
                            $state = $state['value'] ?? reset($state);
                        }

                        $classId = $state ?: $defaultClassId;

                        if (! $classId) {
                            return $query->whereRaw('1 = 0');
                        }

                        return $query->where('enrollments.class_id', $classId);
                    }),

                // DISCIPLINA – opcional, só usada nos cálculos / gravação
                SelectFilter::make('subject_id')
                    ->label('Disciplina')
                    ->options(function () use ($teacher) {
                        if (! $teacher) {
                            return [];
                        }

                        return $teacher->teacherAssignments()
                            ->join('subjects', 'subjects.id', '=', 'teacher_assignments.subject_id')
                            ->orderBy('subjects.name')
                            ->pluck('subjects.name', 'subjects.id');
                    })
                    // disciplina não altera a query dos alunos, só o contexto de Attendance
                    ->query(fn (Builder $query, $state) => $query),

                // DATA – para a chamada do dia
                Filter::make('date')
                    ->label('Data da aula')
                    ->form([
                        DatePicker::make('value')
                            ->label('Data')
                            ->default(now())
                            ->required(),
                    ])
                    ->default([
                        'value' => now()->toDateString(),
                    ])
                    // 💡 Mostra o chip "Data: 27/10/2025" nos filtros ativos
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['value'])) {
                            return null;
                        }

                        $date = Carbon::parse($data['value'])->format('d/m/Y');

                        return "Data: {$date}";
                    })
                    // opcional: mantém a query como está, só usa o filtro como "contexto"
                    ->query(function (Builder $query, array $data): Builder {
                        return $query;
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('registration_number')
                    ->label('Matrícula'),

                TextColumn::make('name')
                    ->label('Aluno')
                    ->searchable(),

                TextColumn::make('attendance_present_count')
                    ->label('Presenças')
                    ->alignCenter()
                    ->state(function (Student $record) {
                        return Attendance::where('student_id', $record->id)
                            ->where('class_id', $record->enrollment_class_id)
                            ->where('status', 'present') // ajuste se usar enum/const
                            ->count();
                    }),

                TextColumn::make('attendance_absent_count')
                    ->label('Faltas')
                    ->alignCenter()
                    ->state(function (Student $record) {
                        return Attendance::where('student_id', $record->id)
                            ->where('class_id', $record->enrollment_class_id)
                            ->where('status', 'absent')
                            ->count();
                    }),

                TextColumn::make('attendance_frequency')
                    ->label('Freq.')
                    ->alignCenter()
                    ->state(function (Student $record) {
                        $base = Attendance::where('student_id', $record->id)
                            ->where('class_id', $record->enrollment_class_id);

                        $total = (clone $base)->count();

                        if ($total === 0) {
                            return '0%';
                        }

                        $present = (clone $base)
                            ->whereIn('status', ['present', 'late'])
                            ->count();

                        $freq = (int) round(($present / $total) * 100);

                        return $freq . '%';
                    })
                    ->color(function ($state) {
                        if (! $state) {
                            return null;
                        }

                        $num = (int) filter_var($state, FILTER_SANITIZE_NUMBER_INT);

                        return $num < 70 ? 'danger' : null;
                    }),

            ])

            ->actions([])

            ->bulkActions([
                BulkAction::make('fecharChamada')
                    ->label('Fechar chamada (selecionados = presentes)')
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        // Aqui data é obrigatória
                        $ctx = $this->resolveContext(true);
                        if (! $ctx) {
                            Notification::make()
                                ->title('Selecione Turma, Data (e disciplina, se quiser) antes de salvar.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $classId   = (int) $ctx['class_id'];
                        $date      = $ctx['date'];
                        $subjectId = $ctx['subject_id']; // pode ser null

                        // alunos selecionados → presentes
                        $presentIds = $records->pluck('id')->all();

                        // todos os alunos da turma (naquele momento)
                        $allStudentsIds = Student::query()
                            ->join('enrollments', 'enrollments.student_id', '=', 'students.id')
                            ->where('enrollments.class_id', $classId)
                            ->pluck('students.id')
                            ->all();

                        $absentIds = array_diff($allStudentsIds, $presentIds);

                        // Presentes
                        foreach ($presentIds as $studentId) {
                            Attendance::updateOrCreate(
                                [
                                    'student_id' => $studentId,
                                    'class_id'   => $classId,
                                    'subject_id' => $subjectId,
                                    'date'       => $date,
                                ],
                                ['status' => 'present'],
                            );
                        }

                        // Faltas
                        foreach ($absentIds as $studentId) {
                            Attendance::updateOrCreate(
                                [
                                    'student_id' => $studentId,
                                    'class_id'   => $classId,
                                    'subject_id' => $subjectId,
                                    'date'       => $date,
                                ],
                                ['status' => 'absent'],
                            );
                        }

                        Notification::make()
                            ->title('Chamada salva')
                            ->body(
                                'Presentes: ' . count($presentIds) .
                                ' • Faltas: ' . count($absentIds)
                            )
                            ->success()
                            ->send();

                        $this->resetTable();
                    }),
            ]);
    }

    /**
     * Lê os filtros e devolve:
     *  - class_id (sempre)
     *  - subject_id (pode ser null)
     *  - date (pode ser null se $requireDate = false)
     */
    protected function resolveContext(bool $requireDate = true): ?array
    {
        // ⚠️ Este método só é chamado dentro de callbacks da tabela
        // (colunas / bulkActions), quando a tabela já foi inicializada.
        $filters = $this->getTableFiltersForm()->getState();

        $classId   = $filters['class_id']   ?? null;
        $subjectId = $filters['subject_id'] ?? null;
        $dateRaw   = $filters['date']['value'] ?? null;

        if (is_array($classId)) {
            $classId = $classId['value'] ?? reset($classId);
        }

        if (is_array($subjectId)) {
            $subjectId = $subjectId['value'] ?? reset($subjectId);
        }

        if (! $classId) {
            return null;
        }

        // quando só estamos calculando totais, a data pode ser opcional
        if ($requireDate && ! $dateRaw) {
            return null;
        }

        $date = $dateRaw ? Carbon::parse($dateRaw)->toDateString() : null;

        return [
            'class_id'   => (int) $classId,
            'subject_id' => $subjectId ?: null,
            'date'       => $date,
        ];
    }
}
