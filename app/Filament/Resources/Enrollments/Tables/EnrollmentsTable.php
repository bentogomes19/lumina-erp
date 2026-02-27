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
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
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

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors(EnrollmentStatus::colors())
                    ->formatStateUsing(function ($state) {
                        if ($state instanceof EnrollmentStatus) {
                            return $state->value;
                        }
                        return (string) $state;
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
