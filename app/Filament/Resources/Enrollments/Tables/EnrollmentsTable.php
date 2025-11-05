<?php

namespace App\Filament\Resources\Enrollments\Tables;

use App\Enums\EnrollmentStatus;
use App\Enums\Term;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
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
                // 🔹 Filtro por ano letivo via relacionamento
                // 🔹 Novo filtro: ano letivo, já vindo com o ativo selecionado
                SelectFilter::make('school_year_id')
                    ->label('Ano letivo')
                    ->relationship('schoolClass.schoolYear', 'year')
                    ->default(fn () => SchoolYear::where('is_active', true)->value('id')),

                // 🔹 Filtro por turma via relacionamento
                SelectFilter::make('class_id')
                    ->label('Turma')
                    ->relationship('class', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(EnrollmentStatus::options()),

                SelectFilter::make('term')
                    ->label('Período')
                    ->options(Term::options()),
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
                            foreach ($records as $enr) {
                                $exists = Enrollment::where('student_id', $enr->student_id)
                                    ->where('class_id', $data['class_id'])
                                    ->exists();

                                if (! $exists) {
                                    $enr->update([
                                        'class_id'    => $data['class_id'],
                                        'roll_number' => Enrollment::nextRollNumberFor((int) $data['class_id']),
                                    ]);
                                }
                            }
                        }),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
