<?php

namespace App\Filament\Resources\Enrollments\Tables;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // 🔹 Deixa o query explícito e já com as relações carregadas
            ->query(fn () => Enrollment::query()
                ->with([
                    'student',
                    'class.gradeLevel',
                    'class.schoolYear',
                ])
            )
            ->defaultSort('enrollment_date', 'desc')

            ->columns([
                TextColumn::make('student.registration_number')
                    ->label('Matrícula')
                    ->searchable(),

                TextColumn::make('student.name')
                    ->label('Aluno')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('class.name')
                    ->label('Turma')
                    ->sortable(),

                TextColumn::make('class.gradeLevel.name')
                    ->label('Série')
                    ->toggleable(),

                TextColumn::make('class.schoolYear.year')
                    ->label('Ano')
                    ->sortable(),

                TextColumn::make('roll_number')
                    ->label('Nº')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof EnrollmentStatus ? $state->value : (string) $state)
                    ->color(fn ($state) => match (true) {
                        $state instanceof EnrollmentStatus => match ($state) {
                            EnrollmentStatus::ACTIVE => 'success',
                            EnrollmentStatus::SUSPENDED => 'warning',
                            EnrollmentStatus::CANCELED => 'danger',
                            EnrollmentStatus::COMPLETED => 'info',
                            default => 'gray',
                        },
                        $state === 'Ativa' => 'success',
                        $state === 'Suspensa' => 'warning',
                        $state === 'Cancelada' => 'danger',
                        $state === 'Completa' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('enrollment_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
            ])

            ->filters([
                SelectFilter::make('school_year_id')
                    ->label('Ano letivo')
                    ->options(fn () => SchoolYear::orderByDesc('year')->pluck('year', 'id'))
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? $data['school_year_id'] ?? null;
                        if (filled($value)) {
                            $query->whereHas('class', fn ($q) => $q->where('school_year_id', $value));
                        }
                    })
                    ->default(fn () => SchoolYear::where('is_active', true)->value('id')),

                SelectFilter::make('class_id')
                    ->label('Turma')
                    ->relationship('class', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(EnrollmentStatus::options()),
            ])

            ->recordActions([
                ViewAction::make()
                    ->label('Ver')
                    ->modalHeading('Dados da matrícula')
                    ->modalDescription('Informações somente leitura desta matrícula.')
                    ->mutateRecordDataUsing(function (array $data, Enrollment $record): array {
                        $data['student_name'] = $record->student?->name;
                        $data['student_registration_number'] = $record->student?->registration_number;
                        $data['class_name'] = $record->class?->name;
                        $data['grade_level'] = $record->class?->gradeLevel?->name;
                        $data['school_year'] = $record->class?->schoolYear?->year;
                        $data['enrollment_date_formatted'] = $record->enrollment_date?->format('d/m/Y');
                        $data['status_label'] = $record->status instanceof EnrollmentStatus
                            ? $record->status->value
                            : (string) $record->status;
                        return $data;
                    })
                    ->form([
                        TextInput::make('student_registration_number')
                            ->label('Nº Matrícula (aluno)')
                            ->disabled(),
                        TextInput::make('student_name')
                            ->label('Aluno')
                            ->disabled(),
                        TextInput::make('class_name')
                            ->label('Turma')
                            ->disabled(),
                        TextInput::make('grade_level')
                            ->label('Série')
                            ->disabled(),
                        TextInput::make('school_year')
                            ->label('Ano letivo')
                            ->disabled(),
                        TextInput::make('roll_number')
                            ->label('Nº de chamada')
                            ->disabled(),
                        TextInput::make('enrollment_date_formatted')
                            ->label('Data da matrícula')
                            ->disabled(),
                        TextInput::make('status_label')
                            ->label('Status')
                            ->disabled(),
                    ]),
                EditAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkStatus')
                        ->label('Alterar status')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->form([
                            Select::make('status')
                                ->options(EnrollmentStatus::options())
                                ->required(),
                        ])
                        ->action(fn ($records, $data) => $records->each->update([
                            'status' => $data['status'],
                        ])),

                    BulkAction::make('transfer')
                        ->label('Transferir turma')
                        ->icon('heroicon-o-arrow-right')
                        ->form([
                            Select::make('class_id')
                                ->label('Nova Turma')
                                ->options(fn () => SchoolClass::with('gradeLevel', 'schoolYear')
                                    ->get()
                                    ->mapWithKeys(fn ($c) => [
                                        $c->id => "{$c->name} — {$c->gradeLevel?->name} ({$c->schoolYear?->year})",
                                    ])
                                )
                                ->required(),
                        ])
                        ->action(function ($records, $data) {
                            $updated = 0;
                            foreach ($records as $enr) {
                                $exists = Enrollment::where('student_id', $enr->student_id)
                                    ->where('class_id', $data['class_id'])
                                    ->exists();

                                if (! $exists) {
                                    $enr->update([
                                        'class_id'    => $data['class_id'],
                                        'roll_number' => Enrollment::nextRollNumberFor((int) $data['class_id']),
                                    ]);
                                    $updated++;
                                }
                            }
                            if ($updated > 0) {
                                Notification::make()
                                    ->title('Transferência de turma')
                                    ->body("{$updated} matrícula(s) transferida(s).")
                                    ->success()
                                    ->send();
                            }
                        }),

                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $user = auth()->user();
                            $deleted = 0;
                            $blocked = 0;
                            foreach ($records as $enrollment) {
                                if ($user->can('delete', $enrollment)) {
                                    $enrollment->delete();
                                    $deleted++;
                                } else {
                                    $blocked++;
                                }
                            }
                            if ($blocked > 0) {
                                Notification::make()
                                    ->title('Exclusão em lote')
                                    ->body($deleted > 0
                                        ? "{$deleted} matrícula(s) excluída(s). {$blocked} não puderam ser excluídas (possuem notas lançadas). Cancele a matrícula em vez de excluir."
                                        : "Nenhuma matrícula excluída. Matrículas com notas lançadas não podem ser excluídas. Cancele o status da matrícula em vez de excluir.")
                                    ->warning()
                                    ->send();
                            } elseif ($deleted > 0) {
                                Notification::make()
                                    ->title('Matrículas excluídas')
                                    ->body("{$deleted} matrícula(s) excluída(s).")
                                    ->success()
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }
}
